<?php

namespace BlueSpice\UEModuleBookPDF;

use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\UniversalExport\IExportDialogPlugin;
use IContextSource;
use MediaWiki\MediaWikiServices;
use Title;

class ExportDialogPluginBook implements IExportDialogPlugin {

	/** @var Config */
	private $config = null;

	/** @var BookLookup */
	private $bookLookup = null;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$this->bookLookup = $services->getService( 'BSBookshelfBookLookup' );
	}

	/**
	 * @return void
	 */
	public static function factory() {
		return new static();
	}

	/**
	 *
	 * @return array
	 */
	public function getRLModules(): array {
		return [ "ext.bluespice.ueModuleBookPDF.ue-export-dialog-plugin-bookpdf" ];
	}

	/**
	 *
	 * @return array
	 */
	public function getJsConfigVars(): array {
		$defaultTemplate = $this->config->get( 'UEModuleBookPDFDefaultTemplate' );
		$defaultTemplatePath = $this->config->get( 'UEModuleBookPDFTemplatePath' );
		$excludeList = $this->config->get( 'UEModuleBookPDFExportDialogExcludeTemplates' );

		$availableTemplates = [];
		$dir = opendir( $defaultTemplatePath );
		$subDir = readdir( $dir );
		while ( $subDir !== false ) {
			if ( in_array( $subDir, [ '.', '..', 'common' ] ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( !is_dir( "{$defaultTemplatePath}/{$subDir}" ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( in_array( $subDir, $excludeList ) ) {
				$subDir = readdir( $dir );
				continue;
			}

			if ( file_exists( "{$defaultTemplatePath}/{$subDir}/template.php" ) ) {
				$availableTemplates[] = $subDir;
			}

			$subDir = readdir( $dir );
		}

		$jsConfigVars = [];
		if ( empty( $availableTemplates ) ) {
			$defaultTemplate = '';
		} else {
			if ( !in_array( $defaultTemplate, $availableTemplates ) ) {
				$defaultTemplate = $availableTemplates[0];
			}
		}

		$jsConfigVars = [
			'bsUEModuleBookPDFDefaultTemplate' => $defaultTemplate,
			'bsUEModuleBookPDFAvailableTemplates' => $availableTemplates
		];

		return $jsConfigVars;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function skip( IContextSource $context ): bool {
		$title = $context->getSkin()->getRelevantTitle();

		if ( $title instanceof Title === false ) {
			return true;
		}

		if ( $title->getContentModel() !== CONTENT_MODEL_WIKITEXT ) {
			return true;
		}

		$books = $this->bookLookup->getBooksForPage( $title );
		if ( empty( $books ) ) {
			return true;
		}

		return false;
	}
}
