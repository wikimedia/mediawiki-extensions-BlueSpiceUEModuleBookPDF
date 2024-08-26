<?php

/**
 * @copyright Copyright (C) 2016 Hallo Welt! GmbH
 * @author Daniel Vogel
 */

use BlueSpice\UniversalExport\ExportModule;
use BlueSpice\UniversalExport\ExportSpecification;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IMaintainableDatabase;

require_once dirname( dirname( __DIR__ ) ) . "/BlueSpiceFoundation/maintenance/BSMaintenance.php";

class DumpPDF extends BSMaintenance {

	/**
	 * @var string
	 */
	private $bookTitle = '';

	/**
	 * @var string
	 */
	private $bookSubTitle = '';

	/**
	 * @var array
	 */
	private $splitGroups = [
		'ABC', 'DEF', 'GHI', 'JKL', 'MNO', 'PQR', 'STU', 'VWX', 'YZ', '0123456789'
	];

	/**
	 * @var bool
	 */
	private $verbose = false;

	/**
	 * @var MailAddress|null
	 */
	private $mailAddress = null;

	/**
	 * @var array
	 */
	private $mailData = [];

	public function __construct() {
		parent::__construct();

		$this->addOption( 'file', 'Absolute path of the output pdf file.', true, true, 'f' );
		$this->addOption(
			'limit',
			'Limit of pages for single pdf export. If more pages are in the namespace the pdf is split.',
			false, true, 'l'
		);
		$this->addOption( 'verbose', 'Verbose output', false, false, 'v' );
		$this->addOption( 'mail', 'E-mail address for notification email', false, true, 'm' );
	}

	public function execute() {
		$this->setupEnv();

		if ( $this->verbose ) {
			$this->output( "Starting wiki dump...\n" );
		}

		$this->setVerboseState();
		$this->setEmailAddress();

		$this->exportContentPages();

		if ( $this->mailAddress instanceof MailAddress ) {
			$this->sendMail();
		}

		if ( $this->verbose ) {
			$this->output( "Complete\n" );
		}
	}

	/**
	 * Set global variables and context needed for various integrations of the export
	 *
	 * @return void
	 * @throws MWException
	 */
	private function setupEnv() {
		$this->setGlobals();
		$this->overrideUser();

		$context = RequestContext::getMain();
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BeforeInitialize',
			[
				Title::newMainPage(), null, $context->getOutput(), $this->getUser(),
				$context->getRequest(), new MediaWiki()
			]
		);
	}

	/**
	 * Collect pages from content namespaces
	 * and split them.
	 *
	 * @return void
	 * @throws MWException
	 */
	private function exportContentPages() {
		/** @var IMaintainableDatabase */
		$dbr = $this->getDB( DB_REPLICA );

		$services = MediaWikiServices::getInstance();
		$namespaces = $services->getNamespaceInfo()->getContentNamespaces();

		foreach ( $namespaces as $namespace ) {
			$res = $dbr->select(
				'page',
				[ 'page_id', 'page_namespace', 'page_title' ],
				[ 'page_namespace' => [ $namespace ] ],
				__METHOD__,
				[
					'sort' => 'ASC'
				]
			);

			$result = [];
			foreach ( $res as $row ) {
				$result[] = $row;
			}

			$limit = $this->getOption( 'limit', false );
			if ( ( $limit !== false ) && ( count( $result ) > $limit ) ) {
				$this->makeSplitPDF( $result, $namespace );
			} else {
				$this->makeSinglePDF( $result, $namespace );
			}
		}
	}

	/**
	 * @param array $res
	 * @param string $namespace
	 *
	 * @return void
	 */
	private function makeSplitPDF( $res, $namespace ) {
		$pages = [];
		foreach ( $res as $row ) {
			$title = Title::newFromId( $row->page_id );

			if ( $title instanceof Title === false ) {
				continue;
			}

			$pages[$title->getText()] = $row;
		}

		ksort( $pages );

		$splitPages = [];
		foreach ( $pages as $key => $row ) {
			$firstChar = substr( $key, 0, 1 );

			foreach ( $this->splitGroups as $group ) {
				if ( strstr( $group, $firstChar, false ) === false ) {
					continue;
				}

				if ( !isset( $splitPages[$group] ) ) {
					$splitPages[$group] = [];
				}

				$splitPages[$group][] = $row;

				break;
			}
		}

		foreach ( $splitPages as $key => $res ) {
			$this->makeSinglePDF( $res, $namespace, $key );
		}
	}

	/**
	 * @param array $res
	 * @param string $namespace
	 * @param string $addon
	 *
	 * @return void
	 * @throws MWException
	 */
	private function makeSinglePDF( $res, $namespace, $addon = '' ) {
		$articles = [];
		$counter = 0;
		$namespaceText = '';
		foreach ( $res as $row ) {
			/** @var Title */
			$title = Title::newFromId( $row->page_id );

			if ( $title instanceof Title === false ) {
				continue;
			}

			$counter++;

			if ( $namespaceText === '' ) {
				$namespaceText = $title->getNsText();
			}

			if ( $namespaceText === '' ) {
				$namespaceText = 'Seiten';
			}

			$articles[] = $this->makeArticleData( $title, $counter );
		}

		$filenameAddon = $namespaceText;
		if ( $addon !== '' ) {
			$filenameAddon .= '_' . $addon;
		}

		if ( !empty( $articles ) ) {
			if ( $this->verbose ) {
				$this->output( "Namespace: $namespace" );
			}

			$this->setBookTitle( $namespaceText, $addon );

			$specData = $this->getCommonSpecData( $filenameAddon );
			$specData['articles'] = FormatJSON::encode( $articles );

			$specs = $this->getSpecFromSpecData( Title::newMainPage(), $specData );
			$status = $this->makePDF( $specs );

			$this->mailData[$namespaceText][] = [
				'filename' => $specData['target-file-name'],
				'article_count' => count( $articles ),
				'status' => $status
			];
		}
	}

	/**
	 * @return void
	 */
	private function setGlobals() {
		$GLOBALS['wgHTTPProxy'] = false;
		$GLOBALS['wgLocalVirtualHosts'] = [];
		$GLOBALS['wgGroupPermissions']['*']['read'] = true;
		$GLOBALS['bsgGroupRoles']['*']['reader'] = true;
		$GLOBALS['bsgNamespaceRolesLockdown'] = [];
	}

	/**
	 * @return void
	 */
	private function overrideUser() {
		RequestContext::getMain()->setUser( $this->getUser() );
		$GLOBALS['wgUser'] = $this->getUser();
	}

	/**
	 * @return User
	 * @throws MWException
	 */
	private function getUser() {
		/** @var \BlueSpice\UtilityFactory $util */
		$util = MediaWikiServices::getInstance()->getService( 'BSUtilityFactory' );
		return $util->getMaintenanceUser()->getUser();
	}

	/**
	 * @param string $addon
	 * @return array
	 */
	private function getCommonSpecData( string $addon = '' ) {
		$targetFile = $this->getTargetFile( $addon );
		$specData = [
			'module' => 'bookpdf',
			'target' => 'localfilesystem',
			'target-file-name' => $targetFile['basename'],
			'target-file-path' => $targetFile['dirname']
		];

		return $specData;
	}

	/**
	 * Get specified target file specs
	 *
	 * @param string $addon
	 * @return string[]
	 */
	private function getTargetFile( string $addon = '' ) {
		$targetFile = pathinfo( $this->getOption( 'file', '' ) );

		$path = $targetFile['dirname'];

		if ( $targetFile['dirname'] === '.' ) {
			$targetFile['dirname'] = __DIR__;
		}

		if ( $addon !== '' ) {
			$newFilename = $targetFile['filename'] . '_' . $addon;
			$newBasename = $newFilename . '.' . $targetFile['extension'];

			$targetFile['filename'] = $newFilename;
			$targetFile['basename'] = $newBasename;
		}

		$path .= '/' . $targetFile['basename'];

		if ( $this->verbose ) {
			$this->output( "File:      $path\n" );
		}

		return $targetFile;
	}

	/**
	 * @param Title $title
	 * @param array $specData
	 *
	 * @return ExportSpecification
	 * @throws MWException
	 */
	private function getSpecFromSpecData( $title, $specData ) {
		return MediaWikiServices::getInstance()
			->getService( 'BSUniversalExportSpecificationFactory' )
			->newSpecification(
				$title,
				$this->getUser(),
				$specData
		);
	}

	/**
	 * @param ExportSpecification $specs
	 * @throws MWException
	 * @return bool
	 */
	private function makePDF( ExportSpecification $specs ): bool {
		$moduleName = $specs->getParam( 'module', null );

		/** @var ExportModule $module */
		$module = $this->getModuleFactory()->newFromName( $moduleName );
		if ( $module === null ) {
			$this->fatalError(
				'Requested export module not found'
			);
		}

		try {
			$file = $module->createExportFile( $specs );
			$module->invokeExportTarget( $file, $specs );
		} catch ( Exception $e ) {
			$this->error( "Export failed:" . $e->getMessage() );
			return false;
		}

		return true;
	}

	/**
	 * @return \BlueSpice\UniversalExport\ModuleFactory
	 */
	private function getModuleFactory() {
		$services = MediaWikiServices::getInstance();
		return $services->getService( 'BSUniversalExportModuleFactory' );
	}

	/**
	 * @param Title $title
	 * @param int $chapterNumber
	 *
	 * @return array
	 */
	private function makeArticleData( Title $title, int $chapterNumber ): array {
		return [
			'namespace' => $title->getNamespace(),
			'title' => $title->getDBkey(),
			'name' => $title->getPrefixedText(),
			'number' => $chapterNumber,
			'type' => 'wikilink-with-alias',
		];
	}

	/**
	 * @param string $bookTitle
	 * @param string $bookSubTitle
	 * @return void
	 */
	private function setBookTitle( string $bookTitle, string $bookSubTitle ) {
		$this->bookTitle = str_replace( '_', ' ', $bookTitle );
		$this->bookSubTitle = $bookSubTitle;

		if ( $this->bookTitle === '' ) {
			$this->bookTitle = '(Seiten)';
		}

		$GLOBALS['wgHooks']['BSUEModulePDFcollectMetaData'][] = function (
			$title, $pageDOM, &$params, $DOMXPath, &$meta
		) {
			$meta['title'] = $this->bookTitle;
			$meta['subtitle'] = $this->bookSubTitle;
		};
	}

	/**
	 * @return void
	 */
	private function setVerboseState(): void {
		$verbose = $this->getOption( 'verbose', false );

		if ( !$verbose ) {
			$this->verbose = false;
		} else {
			$this->verbose = true;
		}
	}

	/**
	 * @return void
	 */
	private function setEmailAddress(): void {
		$mail = $this->getOption( 'mail', null );

		if ( !$mail ) {
			$this->mailAddress = null;
		} elseif ( Sanitizer::validateEmail( $mail ) ) {
			$this->mailAddress = new MailAddress( $mail );
		} else {
			$this->mailAddress = null;
		}
	}

	/**
	 * @return MailAddress|false
	 */
	private function getMailSenderAddress(): ?MailAddress {
		$sender = $GLOBALS['wgPasswordSender'];

		if ( !Sanitizer::validateEmail( $sender ) ) {
			return false;
		}

		return new MailAddress( $sender );
	}

	private function sendMail() {
		if ( $this->mailAddress == null ) {
			$this->error(
				'Not a valid user name or e-mail address or user has no e-mail address set.'
			);
		} elseif ( $this->getMailSenderAddress() === false ) {
			$this->error(
				'wgPasswordSender not valid.'
			);
		} else {
			$status = UserMailer::send(
				$this->mailAddress,
				$this->getMailSenderAddress(),
				$this->getMailSubject(),
				$this->getMailBody()
			);

			if ( $this->verbose === true ) {
				if ( $status->isGood() ) {
					$this->output( "Mail send\n" );
				} else {
					$this->output( "Mail error: " . $status->getMessage() );
				}
			}
		}
	}

	/**
	 * @return string
	 */
	private function getMailSubject(): string {
		$sitename = $GLOBALS['wgSitename'];
		$subject = "$sitename - Automatischer pdf Export";

		return $subject;
	}

	/**
	 * @return string
	 */
	private function getMailBody(): string {
		$body = '';

		$sitename = $GLOBALS['wgSitename'];
		$timestampNow = wfTimestampNow();
		$timestamp = wfTimestamp( TS_RFC2822, $timestampNow );
		$timestampParts = explode( ',', $timestamp );
		array_shift( $timestampParts );
		$timestamp = implode( ',', $timestampParts );

		$body = 'Der automatische pdf Export ist am ' .
			trim( $timestamp ) . ' für "' . $sitename . '" gelaufen.' . "\n\n";

		foreach ( $this->mailData as $namespaceText => $namespaceData ) {
			$body .= "\n" . $namespaceText . ":\n";

			foreach ( $namespaceData as $data ) {
				if ( $data['status'] === true ) {
					$body .= '- ' . $data['filename'] . ' - ' . $data['article_count'] . " Wiki Seite(n)\n";
				} else {
					$body .= '- ' . $data['filename'] . ' - FEHLER' . "\n";
				}
			}
		}

		return $body;
	}
}

$maintClass = DumpPDF::class;
require_once RUN_MAINTENANCE_IF_MAIN;
