<?php

use BlueSpice\Bookshelf\BookContextProviderFactory;
use BlueSpice\Bookshelf\BookLookup;
use BlueSpice\Bookshelf\BookMetaLookup;
use BlueSpice\Bookshelf\ChapterLookup;
use BlueSpice\UEModuleBookPDF\BookmarksXMLBuilder;
use BlueSpice\UEModulePDF\PDFServletHookRunner;
use BlueSpice\UniversalExport\ExportModule;
use BlueSpice\UniversalExport\ExportSpecification;
use BlueSpice\UniversalExport\IExportModule;
use MediaWiki\MediaWikiServices;
use MediaWiki\Session\Session;
use MediaWiki\Session\SessionManager;

class BsBookExportModulePDF extends ExportModule {

	/**
	 * @var Session
	 */
	private $session;

	/**
	 *
	 * @var array
	 */
	private $flatBookmarksList = [];

	/**
	 * @var string|bool
	 */
	protected $bookType = false;

	/**
	 * @var string|bool
	 */
	protected $content = false;

	/**
	 * @param string $name
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param WebRequest|null $request
	 * @return IExportModule
	 */
	public static function factory(
		$name, MediaWikiServices $services, Config $config, $request = null
	) {
		if ( !$request ) {
			$request = RequestContext::getMain()->getRequest();
		}
		return new static( $name, $services, $config, $request, SessionManager::getGlobalSession() );
	}

	/**
	 * @param string $name
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param WebRequest $request
	 * @param Session $session
	 */
	protected function __construct(
		$name, MediaWikiServices $services, Config $config, WebRequest $request, Session $session
	) {
		parent::__construct( $name, $services, $config, $request );
		$this->session = $session;
	}

	/**
	 * Implementation of BsUniversalExportModule interface. Uses the
	 * PdfWebservice BN2PDF-light to create a PDF file.
	 * @param ExportSpecification &$specification
	 * @return array array(
	 *     'mime-type' => 'application/pdf',
	 *     'filename' => 'Filename.pdf',
	 *     'content' => '8F3BC3025A7...'
	 * );
	 * @throws ConfigException
	 * @throws FatalError
	 * @throws MWException
	 * @throws PermissionsError
	 */
	public function createExportFile( ExportSpecification &$specification ) {
		// Prepare response
		$aResponse = [
			'mime-type' => 'application/pdf',
			'filename'  => '%s.pdf',
			'content'   => ''
		];

		$this->bookType = $specification->getParam( 'book_type', false );
		$this->content = $specification->getParam( 'content', false );
		if ( $specification->getTitle()->getNamespace() === NS_BOOK ) {
			$this->session->set( 'forced_book', $specification->getTitle()->getPrefixedDBkey() );
		}
		$chaptersFromSpec = FormatJson::decode(
			$specification->getParam( 'articles', '[]' ), true
		);
		// If we are on a page included in a book
		/** @var BookContextProviderFactory $bookContextProviderFactory */
		$bookContextProviderFactory = $this->services->getService( 'BSBookshelfBookContextProviderFactory' );
		$bookContextProvider = $bookContextProviderFactory->getProvider( $specification->getTitle() );
		$activeBook = $bookContextProvider->getActiveBook();
		if ( $activeBook instanceof Title === false && empty( $chaptersFromSpec ) ) {
			// No active book and no explicit chapters to export - bail out
			$this->session->remove( 'forced_book' );
			throw new MWException( 'No valid active book found' );
		}

		/** @var TitleFactory */
		$titleFactory = $this->services->getTitleFactory();

		// "articles" is legacy naming. Should be 'nodes'
		$aArticles = [];

		if ( !empty( $chaptersFromSpec ) ) {
			// Articles are passed in URL (e.g. from Book partial export)
			foreach ( $chaptersFromSpec as $chapter ) {
				$article = [
					'type' => 'text',
					'title' => null,
					'display-title' => $chapter['name'],
					'number' => $chapter['number']
				];

				if ( $chapter['type'] === 'wikilink-with-alias' ) {
					$articleTitle = $titleFactory->makeTitle( $chapter['namespace'], $chapter['title'] );
					$article['title'] = $articleTitle->getPrefixedText();
					$article['article-id'] = $articleTitle->getArticleId();
					$article['type'] = 'wikipage';
				}

				$aArticles[] = $article;
			}
		} else {
			// Entire book export call

			/** @var ChapterLookup */
			$bookChapterLookup = $this->services->getService( 'BSBookshelfBookChapterLookup' );
			$chapters = $bookChapterLookup->getChaptersOfBook( $activeBook );

			foreach ( $chapters as $chapter ) {
				$article = [
					'type' => 'text',
					'title' => null,
					'display-title' => $chapter->getName(),
					'number' => $chapter->getNumber()
				];

				if ( $chapter->getType() === 'wikilink-with-alias' ) {
					$articleTitle = $titleFactory->makeTitle( $chapter->getNamespace(), $chapter->getTitle() );
					$article['title'] = $articleTitle->getPrefixedText();
					$article['article-id'] = $articleTitle->getArticleId();
					$article['type'] = 'wikipage';
				}

				$aArticles[] = $article;
			}
		}

		$aBookMeta = [];
		$aBookPage = [
			'meta' => [ 'title' => $specification->getTitle()->getPrefixedText() ]
		];
		if ( $activeBook ) {
			/** @var BookMetaLookup */
			$bookMetaLookup = $this->services->getService( 'BSBookshelfBookMetaLookup' );
			$aBookMeta = $bookMetaLookup->getMetaForBook( $activeBook );
			try {
				$aBookPage = BsPDFPageProvider::getPage( [
					'article-id' => $activeBook->getArticleId(),
					'follow-redirects' => true
				] );
			} catch ( Exception $ex ) {
				// Fall back to defaults
			}
		}

		$aTemplate = $this->getBookTemplate( $specification, $aBookPage, $aBookMeta );
		if ( isset( $aTemplate['title-element'] )
			&& count( $aTemplate['title-element']->childNodes ) === 0 ) {
			// <title> not set by template
			$aTemplate['title-element']->appendChild(
				$aTemplate['dom']->createTextNode( $specification->getTitle()->getPrefixedText() )
			);
		}

		$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );
		if ( !$config->get( 'BookshelfTitleDisplayText' ) ) {
			for ( $index = 0; $index < count( $aArticles ); $index++ ) {
				$title = Title::newFromText( $aArticles[$index]['title'] );
				if ( $title ) {
					$aArticles[$index]['display-title'] = $this->getDisplayTitle( $title );
				}
			}
		}

		$this->services->getHookContainer()->run(
			'BSBookshelfExportBeforeArticles',
			[
				&$aTemplate,
				&$aBookPage,
				&$aArticles,
				$specification
			]
		);

		// Prepare TOC Page
		$oTOCPage = $aTemplate['dom']->createElement( 'div' );
		$oTOCPage->setAttribute( 'class', 'bs-section bs-custompage bs-tableofcontentspage' );
		$oTOCDiv = $oTOCPage->appendChild(
			$aTemplate['dom']->createElement( 'div' )
		);
		$oTOCDiv->setAttribute( 'class', 'toc' );
		$oTOCTitleDiv = $oTOCDiv->appendChild(
			$aTemplate['dom']->createElement( 'div' )
		);
		$oTOCTitleDiv->setAttribute( 'class', 'toctitle' );
		$oTOCTitleDiv->appendChild(
			$aTemplate['dom']->createElement(
				'h2',
				wfMessage( 'bs-uemodulebookpdf-table-of-contents-heading' )->text()
			)
		);
		$oTOCList = $oTOCDiv->appendChild(
			$aTemplate['dom']->createElement( 'ul' )
		);

		$oMainContNode = $aTemplate['content-elements']['content'];

		// Insert TOC page as first page (but after external insertions)
		$oMainContNode->parentNode->insertBefore( $oTOCPage, $oMainContNode );

		$beforeContent = $aTemplate['dom']->createElement( 'div', 'Start of book content' );
		$beforeContent->setAttribute( 'class', 'before-first-chapter' );
		$beforeContent->setAttribute( 'style', 'display:none;' );
		$oMainContNode->parentNode->insertBefore( $beforeContent, $oMainContNode );

		$aLinkMap = [];

		$user = $specification->getUser();
		$pm = $this->services->getPermissionManager();
		foreach ( $aArticles as $aArticle ) {
			$aArticle['title'] = urldecode( $aArticle['title'] );
			$aArticle['php'] = [
				'title' => $specification->getTitle()->getPrefixedText(),
				'book_type' => $this->bookType,
				'content' => $this->content,
			];

			$oCurTitle = $titleFactory->newFromText( $aArticle['title'] );

			if ( $oCurTitle instanceof Title &&
				!$pm->userCan( 'uemodulebookpdf-export', $user, $oCurTitle )
			) {
				// allow the PDFExport to export error messages and exceptions such
				// as "Permission denied" instead of not delivering the book at all
				if ( !$config->get( 'UEModulePDFAllowPartialExport' ) ) {
					throw new PermissionsError( 'uemodulebookpdf-export' );
				}
			}

			if ( isset( $aArticle['is-redirect'] ) && $aArticle['is-redirect'] === true ) {
				$dbr = $this->services->getDBLoadBalancer()->getConnection( DB_REPLICA );

				$oRedirectTitle = $this->searchLastRedirect( $aArticle['article-id'], $dbr );
				if ( $oRedirectTitle !== false ) {
					$redirectText = $oRedirectTitle->getDBkey();
					$redirectId = $oRedirectTitle->getArticleID();
					$aArticle['article-id'] = $redirectId;
					$aArticle['title'] = $redirectText;
					$aArticle['bookshelf']['page_id'] = $redirectId;
					$aArticle['bookshelf']['page_title'] = $redirectText;
				}
			}

			$oDOMNode = null;
			switch ( $aArticle['type'] ) {
				case 'wikipage':
					$oDOMNode = $this->getDOMNodeForWikiPage(
						$aArticle,
						$aTemplate,
						$oTOCList,
						$aBookMeta,
						$aLinkMap,
						$aBookPage
					);
					break;
				case 'text':
					$oDOMNode = $this->getDOMNodeForText(
						$aArticle,
						$aTemplate,
						$oTOCList,
						$aBookMeta,
						$aLinkMap,
						$aBookPage
					);
					break;
				default:
					$this->services->getHookContainer()->run(
						'BSBookshelfExportUnknownNodeType',
						[
							$oDOMNode,
							$aArticle,
							&$aTemplate,
							$oTOCList,
							$aBookMeta,
							&$aLinkMap,
							&$aBookPage
						]
					);
			}

			// Add the content
			if ( $oDOMNode instanceof DOMNode ) {
				$oMainContNode->parentNode->insertBefore(
					$aTemplate['dom']->importNode( $oDOMNode, true ),
					$oMainContNode
				);
			}
		}

		$afterContent = $aTemplate['dom']->createElement( 'div', 'End of book content' );
		$afterContent->setAttribute( 'class', 'after-last-chapter' );
		$afterContent->setAttribute( 'style', 'display:none;' );
		$oMainContNode->parentNode->insertBefore( $afterContent, $oMainContNode );

		// Don't forget to remove the remaining content elements
		foreach ( $aTemplate['content-elements'] as $sKey => $oNode ) {
			if ( !$oNode instanceof DOMNode ) {
				// Got deleted earlier
				continue;
			}

			$oNode->parentNode->removeChild( $oNode );
		}

		if ( $activeBook ) {
			$this->modifyTOC( $activeBook, $aTemplate, $aLinkMap );
		}
		$this->modifyTemplateAfterContents( $aTemplate, $aBookPage, $specification );
		// Set params for PDF creation
		$token = md5( $specification->getTitle()->getPrefixedText() ) .
			'-' . intval( $specification->getParam( 'oldid' ) );
		$specification->setParam( 'document-token', $token );
		$specification->setParam( 'soap-service-url', $config->get(
			'UEModulePDFPdfServiceURL'
		) );
		$specification->setParam( 'resources', $aTemplate['resources'] );
		$specification->setParam( 'attachments', '1' );

		$params = $specification->getParams();
		$hookContainer = $this->getServices()->getHookContainer();
		$hookRunner = new PDFServletHookRunner( $hookContainer );
		$oPdfService = new BsPDFServlet( $params, $hookRunner );
		$aResponse['content'] = $oPdfService->createPDF( $aTemplate['dom'] );

		$aResponse['filename'] = sprintf(
			$aResponse['filename'],
			$specification->getTitle()->getPrefixedText()
		);

		$this->session->remove( 'forced_book' );
		return $aResponse;
	}

	/**
	 * @param Title $activeBook
	 * @param array $aTemplate
	 * @param array $aLinkMap
	 * @return void
	 */
	private function modifyTOC( Title $activeBook, array $aTemplate, array $aLinkMap ) {
		/** @var BookLookup */
		$bookLookup = $this->services->getService( 'BSBookshelfBookLookup' );
		$tree = $bookLookup->getBookHierarchy( $activeBook );
		$normalizedTree = [];
		foreach ( $tree as $item ) {
			$this->normalizeTreeItem( $item, $normalizedTree );
		}
		$this->replaceBookmarksElement( $aTemplate, $normalizedTree );

		// Modify internal links
		$oAnchors = $aTemplate['dom']->getElementsByTagName( 'a' );
		foreach ( $oAnchors as $oAnchor ) {
			$sHref  = null;
			$sHref  = $oAnchor->getAttribute( 'href' );
			$sClass = $oAnchor->getAttribute( 'class' );

			if ( empty( $sHref ) ) {
				// Jumplink targets
				continue;
			}

			$aClasses = explode( ' ', $sClass );
			if ( in_array( 'external', $aClasses ) ) {
				continue;
			}

			if ( in_array( 'media', $aClasses ) ) {
				// Going on here would break MEDIA: links
				continue;
			}

			$aHref = parse_url( $sHref );
			if ( !isset( $aHref['path'] ) ) {
				continue;
			}

			$sPathBasename = basename( $aHref['path'] );
			if ( $sPathBasename == 'index.php' && isset( $aHref['query'] ) ) {
				$aQueryString = [];
				parse_str( $aHref['query'], $aQueryString );

				if ( !isset( $aQueryString['title'] ) ) {
					continue;
				}

				$sPathBasename = $aQueryString['title'];
			}

			$sPathBasename = str_replace( '_', ' ', $sPathBasename );

			// Do we have a mapping?
			if ( !isset( $aLinkMap[$sPathBasename] ) ) {
				/*
				 * The following logic is an alternative way of creating internal links
				 * in case of poorly splitted up URLs like mentioned above
				 */
				if ( filter_var( $sHref, FILTER_VALIDATE_URL ) ) {
					$sPathBasename = "";
					$sHrefDecoded = urldecode( $sHref );

					foreach ( $aLinkMap as $linkKey => $linkValue ) {
						if ( strpos( str_replace( '_', ' ', $sHrefDecoded ), $linkKey ) ) {
							$sPathBasename = $linkKey;
						}
					}

					if ( empty( $sPathBasename ) || strlen( $sPathBasename ) <= 0 ) {
						continue;
					}
				}
			}
			if ( empty( $aLinkMap[$sPathBasename] ) ) {
				continue;
			}

			$oAnchor->setAttribute( 'href', $aLinkMap[$sPathBasename] );

			if ( isset( $aHref['fragment'] ) ) {
				$otherPageJumpmark = '#' . md5( $sPathBasename ) . '-' . md5( $aHref['fragment'] );
				$oAnchor->setAttribute( 'href', $otherPageJumpmark );
			}
		}
	}

	/**
	 * @param array $item
	 * @param array &$data
	 */
	private function normalizeTreeItem( array $item, array &$data ): void {
		$itemData = [];

		$itemData['name'] = $item['chapter_name'];
		$itemData['number'] = $item['chapter_number'];

		if ( isset( $item['chapter_children'] ) && !empty( $item['chapter_children'] ) ) {
			foreach ( $item['chapter_children'] as $children ) {
				$itemData['children'] = [];
				$this->normalizeTreeItem( $children, $itemData['children'] );
			}
		}

		if ( !empty( $itemData ) ) {
			$data[] = $itemData;
		}
	}

	/**
	 * @param int $articleId
	 * @param \Wikimedia\Rdbms\IDatabase $dbr
	 * @param array $redirects
	 * @return bool|Title
	 */
	private function searchLastRedirect( $articleId, $dbr, $redirects = [] ) {
		$res = $dbr->select(
			[ 'redirect' ],
			[ '*' ],
			[ 'rd_from' => $articleId ]
		);
		foreach ( $res as $row ) {
			$redirectTitle = $row->rd_title;

			if ( empty( $redirectTitle ) ) {
				return false;
			}

			$oTitle = Title::newFromText( $redirectTitle );
			$redirectId = $oTitle->getArticleID();

			if ( in_array( $redirectId, $redirects ) ) {
				return $oTitle;
			}

			$redirects[] = $redirectId;

			$result = $this->searchLastRedirect( $redirectId, $dbr, $redirects );
			if ( $result === false ) {
				return $oTitle;
			} else {
				return $result;
			}
		}
		return false;
	}

	// <editor-fold desc="Interface BsUniversalExportModule --> getOverview" default-state="collapsed">

	/**
	 * Implementation of BsUniversalExportModule interface. Creates an overview
	 * over the BookshelfExportModule
	 * @return ViewExportModuleOverview
	 */
	public function getOverview() {
		$UEModulePDF = $this->services->getService( 'BSUniversalExportModuleFactory' )->newFromName( 'pdf' );
		$oModuleOverviewView = $UEModulePDF->getOverview();

		$oModuleOverviewView->setOption(
			'module-title',
			wfMessage( 'bs-uemodulebookpdf-overview-title' )->text()
		);
		$oModuleOverviewView->setOption(
			'module-description',
			wfMessage( 'bs-uemodulebookpdf-overview-description' )->text()
		);
		$oModuleOverviewView->setOption(
			'module-bodycontent',
			wfMessage( 'bs-uemodulebookpdf-overview-bodycontent' )->text() . '<br/>'
		);

		return $oModuleOverviewView;
	}

	/**
	 *
	 * @param ExportSpecification $specs
	 * @param array $aBookPage
	 * @param array $aBookMeta
	 * @return array
	 */
	public function getBookTemplate( $specs, $aBookPage, $aBookMeta ) {
		$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );

		$sTemplate = $config->get( 'UEModuleBookPDFDefaultTemplate' );

		if ( isset( $aBookMeta['template'] ) && !empty( $aBookMeta['template'] ) ) {
			$sTemplate = $aBookMeta['template'];
		}

		if ( $specs->getParam( 'template' ) && !empty( $specs->getParam( 'template' ) ) ) {
			$sTemplate = $specs->getParam( 'template' );
		}

		if ( isset( $aBookMeta['title'] ) && !empty( $aBookMeta['title'] ) ) {
			$aBookPage['meta']['title'] = $aBookMeta['title'];
			unset( $aBookMeta['title'] );
		} elseif ( isset( $aBookMeta['title'] ) ) {
			unset( $aBookMeta['title'] );
		}

		$aBookPage['meta'] = array_merge( $aBookPage['meta'], $aBookMeta );

		$aTemplate = BsPDFTemplateProvider::getTemplate( [
			'path'     => $config->get( 'UEModuleBookPDFTemplatePath' ),
			'template' => $sTemplate,
			'language' => $this->getServices()->getUserOptionsLookup()
				->getOption( $specs->getUser(), 'language', 'en' ),
			'meta'     => $aBookPage['meta']
		] );

		return $aTemplate;
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyTemplateAfterContents( &$template, $page, $specification ) {
		$this->services->getHookContainer()->run(
			'BSUEModulePDFBeforeCreatePDF',
			[
				$this,
				$template['dom'],
				$specification
			]
		);
	}

	/**
	 * Calculates the indention and adds an entry to the TOC
	 * @param array $aPage
	 * @param array $aTemplate
	 * @param DOMElement $oTOCList
	 * @param array $aArticle
	 * @param array $aBookMeta
	 * @return int The level of the provided page within the book hierarchy
	 */
	protected function buildTOC( $aPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta ) {
		// Insert entry into TOC Page
		$oTOCListItem = $oTOCList->appendChild(
			$aTemplate['dom']->createElement( 'li' )
		);

		$oTOCListItemLink = $oTOCListItem->appendChild(
			$aTemplate['dom']->createElement( 'a' )
		);
		$oTOCListItemLink->setAttribute( 'href', $aPage['bookmark-element']->getAttribute( 'href' ) );
		$oTOCList->appendChild( $oTOCListItem );

		$sNumberSpanText = isset( $aPage['number'] ) ? $aPage['number'] . ' ' : '';
		$oTOCListItemNumberSpan = $oTOCListItemLink->appendChild(
			$aTemplate['dom']->createElement( 'span', $sNumberSpanText )
		);
		$oTOCListItemNumberSpan->setAttribute( 'class', 'tocnumber' );
		$oTitleText = $aTemplate['dom']->createTextNode( $aArticle['display-title'] );
		$oTOCListItemTextSpan = $aTemplate['dom']->createElement( 'span' );
		$oTOCListItemTextSpan->appendChild( $oTitleText );
		$oTOCListItemTextSpan->setAttribute( 'class', 'toctext' );
		$oTOCListItemLink->appendChild( $oTOCListItemTextSpan );

		$iLevel = 0;
		if ( isset( $aPage['number'] ) ) {
			$number = trim( $aPage['number'], '.' );
			$iLevel = count( explode( '.', $number ) );
		}

		// Check wether to include the article's TOC into the book TOC or not
		$bIncludeArticleTOC = false;
		if ( $aPage['toc-ul-element'] instanceof DOMNode ) {
			$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );
			if ( $config->get( 'UEModuleBookPDFBookExportTOC' ) == 'article-tocs' ) {
				$bIncludeArticleTOC = true;
			}
			if ( isset( $aBookMeta['bookpdf-export-toc'] )
				&& $aBookMeta['bookpdf-export-toc'] == 'article-tocs' ) {
				$bIncludeArticleTOC = true;
			}
		}

		if ( $bIncludeArticleTOC ) {
			// Append articles TOCs to book TOC
			$oTOCListItem->appendChild(
				$aTemplate['dom']->importNode( $aPage['toc-ul-element'], true )
			);
		}

		// Indent article entry in book TOC
		// In a perfect world we would build up a proper ul>li>ul>...
		// structure to model the hierarchy. MW does this since a few versions.
		// But we are stuck with this oldfashioned way of indenting by css
		$sTOCLevelClass = 'toclevel-' . $iLevel;
		$oTOCListItem->setAttribute(
			'class', $oTOCListItem->getAttribute( 'class' ) . ' ' . $sTOCLevelClass
		);

		$this->flatBookmarksList[$aPage['number']] = $aPage['bookmark-element'];
		return $iLevel;
	}

	/**
	 *
	 * @param array $aArticle
	 * @param array &$aTemplate
	 * @param DOMElement $oTOCList
	 * @param array $aBookMeta
	 * @param array &$aLinkMap
	 * @param array &$aBookPage
	 * @return DOMElement|null
	 */
	public function getDOMNodeForWikiPage( $aArticle, &$aTemplate, $oTOCList, $aBookMeta,
		&$aLinkMap, &$aBookPage ) {
		try {
			$aPage = BsPDFPageProvider::getPage( $aArticle );
		} catch ( Throwable $ex ) {
			return null;
		}

		// If there is a number set in the data from the client, it overrides
		// the saved one. This can still be overridden by the hook
		if ( isset( $aArticle['number'] ) ) {
			$aPage['number'] = trim( $aArticle['number'] );
		}

		if ( !isset( $aPage['display-title'] ) ) {
			$aPage['display-title'] = $aArticle['display-title'];
		}

		$this->services->getHookContainer()->run(
			'BSBookshelfExportArticle',
			[
				&$aPage,
				&$aTemplate,
				&$aBookPage,
				&$aArticle
			]
		);

		// Save jumplink to article for later link re-writing
		$aLinkMap[$aArticle['title']] = $aPage['bookmark-element']->getAttribute( 'href' );

		$aPage['bookmark-element']->setAttribute( 'name', $aPage['number'] . ' ' . $aPage['display-title'] );

		$iLevel = $this->buildTOC( $aPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta );

		// Change the headline
		$sDisplayTitle = $aArticle['display-title'];

		$oTitleText = $aPage['firstheading-element']->ownerDocument->createTextNode(
			$sDisplayTitle
		);
		$aPage['firstheading-element']->nodeValue = '';
		$aPage['firstheading-element']->replaceChild(
			$oTitleText,
			$aPage['firstheading-element']->firstChild
		);

		$chapterNumber = isset( $aPage['number'] )
			? $aPage['number']
			: null;

		$numNode = $aPage['dom']->createElement( 'span' );
		$numNode->setAttribute( 'class', 'bs-chapter-number' );
		if ( $chapterNumber ) {
			$numNode->nodeValue = $chapterNumber . ' ';

			// Prepend
			$aPage['firstheading-element']->insertBefore(
				$numNode,
				$aPage['firstheading-element']->firstChild
			);
		}

		$aPage['firstheading-element']->setAttribute(
			'class', $aPage['firstheading-element']->getAttribute( 'class' ) . ' booklevel-' . $iLevel
		);

		/**
		 * $aPage['dom']->documentElement is the <html> element with a nested
		 * <body> with a single child `div.bs-page-content`
		 */
		$bodyEl = $aPage['dom']->getElementsByTagName( 'body' )->item( 0 );

		$this->addBooklevelToSection( $bodyEl, $iLevel );

		return $bodyEl->childNodes[0];
	}

	/**
	 *
	 * @param array $aArticle
	 * @param array &$aTemplate
	 * @param DOMElement $oTOCList
	 * @param array $aBookMeta
	 * @param array &$aLinkMap
	 * @param array &$aBookPage
	 * @return DOMElement
	 */
	public function getDOMNodeForText(
		$aArticle, &$aTemplate, $oTOCList, $aBookMeta,
		&$aLinkMap, &$aBookPage
	) {
		$sDisplayTitle = $aArticle['display-title'];
		$sNumber = '';
		if ( isset( $aArticle['number'] ) ) {
			$sDisplayTitle = trim( $aArticle['number'] ) . ' ' . $aArticle['display-title'];
			$sNumber = trim( $aArticle['number'] ) . ' ';
		}
		$sId = md5( $sDisplayTitle );

		$oDOM = new DOMDocument();
		$oDOM->loadXML( <<<HERE
<xml>
	<div class="bs-section bs-text-content">
		<a name="bs-ue-jumpmark-$sId" id="bs-ue-jumpmark-$sId" />
		<h1 class="firstHeading">
			<span class="bs-chapter-number">$sNumber</span>{$aArticle['display-title']}
		</h1>
	</div>
</xml>
HERE
		);

		$oBooksmarksDOM = new DOMDocument();
		$oBooksmarksDOM->loadXML( <<<HERE
<bookmarks>
	<bookmark name="$sDisplayTitle" href="#bs-ue-jumpmark-$sId"/>
</bookmarks>
HERE
		);

		$oHeading = $oDOM->getElementsByTagName( 'h1' )->item( 0 );
		// See class PDFPageProvider for details
		$aDummyPage = [
			'number' => trim( $aArticle['number'] ),
			'bookmarks-dom' => $oBooksmarksDOM,
			'bookmark-element' => $oBooksmarksDOM->getElementsByTagName( 'bookmark' )->item( 0 ),
			// Dummy
			'toc-ul-element' => ''
		];
		$iLevel = $this->buildTOC( $aDummyPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta );
		$oHeading->setAttribute( 'class', 'booklevel-' . $iLevel );

		$this->addBooklevelToSection( $oDOM, $iLevel );

		return $oDOM->getElementsByTagName( 'div' )->item( 0 );
	}

	/**
	 * Replaces the default <bookmarks /> element from \PdfTemplateProvider with the one containing
	 * the current contents references
	 * @param array $aTemplate
	 * @param array $tree
	 */
	private function replaceBookmarksElement( $aTemplate, $tree ) {
		$bookmarksXMLBuilder = new BookmarksXMLBuilder( $tree );
		$bookmarksElement = $bookmarksXMLBuilder->buildFromFlatBookmarksList(
			$this->flatBookmarksList
		);

		$importedNewBookmarksEl = $aTemplate['dom']->importNode( $bookmarksElement, true );

		$aTemplate['head-element']->removeChild( $aTemplate['bookmarks-element'] );
		$aTemplate['head-element']->appendChild( $importedNewBookmarksEl );
		$aTemplate['bookmarks-element'] = $importedNewBookmarksEl;
	}

	/**
	 * @param \DOMDocument &$node
	 * @param int $level
	 */
	private function addBooklevelToSection( &$node, $level ) {
		$section = $node->getElementsByTagName( 'div' )->item( 0 );
		$section->setAttribute(
			'class', $section->getAttribute( 'class' ) . ' booklevel-' . $level
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getExportPermission() {
		return 'uemodulebookpdf-export';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubactionHandlers() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionButtonDetails() {
		return null;
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	private function getDisplayTitle( Title $title ): string {
		$pageProperties = [];
		$pageProps = $this->services->getPageProps()->getAllProperties( $title );

		$id = $title->getArticleID();

		if ( isset( $pageProps[$id] ) ) {
			$pageProperties = $pageProps[$id];
		}

		if ( isset( $pageProperties['displaytitle'] ) ) {
			return $pageProperties['displaytitle'];
		}

		$config = $this->services->getConfigFactory()->makeConfig( 'bsg' );
		if ( $config->get( 'BookshelfSupressBookNS' ) ) {
			return $title->getText();
		}

		return $title->getPrefixedText();
	}
}
