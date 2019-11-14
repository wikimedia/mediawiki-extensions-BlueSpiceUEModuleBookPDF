<?php
class BsBookExportModulePDF implements BsUniversalExportModule {

	/**
	 * Implementation of BsUniversalExportModule interface. Uses the
	 * PdfWebservice BN2PDF-light to create a PDF file.
	 * @global User $wgUser
	 * @param SpecialUniversalExport $oCaller
	 * @return array array( 'mime-type' => 'application/pdf', 'filename' => 'Filename.pdf', 'content' => '8F3BC3025A7...' );
	 */
	public function createExportFile( &$oCaller ) {
		// Prepare response
		$aResponse = [
			'mime-type' => 'application/pdf',
			'filename'  => '%s.pdf',
			'content'   => ''
		];

		$oPHP = PageHierarchyProvider::getInstanceFor(
			$oCaller->oRequestedTitle->getPrefixedText()
		);
		$aBookMeta = $oPHP->getBookMeta();

		$aArticles = []; // "articles" is legacy naming. Should be 'nodes'
		if ( isset( $oCaller->aParams['articles'] ) ) { // Call from BookEditor
			$aArticles = FormatJson::decode(
				$oCaller->aParams['articles'], true
			);
		} else { // Call from Bookmanager or somewhere else
			$aArticles = $oPHP->getExtendedTOCArray();
		}

		$aBookPage = BsPDFPageProvider::getPage(
			[ 'article-id' => $oCaller->oRequestedTitle->getArticleId(), 'follow-redirects' => true ]
		);

		$aTemplate = $this->getTemplate( $oCaller, $aBookPage, $aBookMeta );
		if ( isset( $aTemplate['title-element'] ) && count( $aTemplate['title-element']->childNodes ) === 0 ) { // <title> not set by template
			$aTemplate['title-element']->appendChild(
				$aTemplate['dom']->createTextNode( $oCaller->oRequestedTitle->getPrefixedText() )
			);
		}

		Hooks::run( 'BSBookshelfExportBeforeArticles',
			[ &$aTemplate, &$aBookPage, &$aArticles ] );

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
			$aTemplate['dom']->createElement( 'h2', wfMessage( 'bs-uemodulebookpdf-table-of-contents-heading' )->text() )
		);
		$oTOCList = $oTOCDiv->appendChild(
			$aTemplate['dom']->createElement( 'ul' )
		);

		$oMainContNode = $aTemplate['content-elements']['content'];

		// Insert TOC page as first page (but after external insertions)
		$oMainContNode->parentNode->insertBefore( $oTOCPage, $oMainContNode );

		$aLinkMap = [];

		foreach ( $aArticles as $aArticle ) {

			$aArticle['title'] = urldecode( $aArticle['title'] );
			$oCurTitle = Title::newFromText( $aArticle['title'] );
			if ( $oCurTitle instanceof Title && !$oCurTitle->userCan( 'uemodulebookpdf-export' ) ) {
				throw new PermissionsError( 'uemodulebookpdf-export' );
			}

			if ( $aArticle['is-redirect'] === true ) {
				$dbr = \MediaWiki\MediaWikiServices::getInstance()
					->getDBLoadBalancer()
					->getConnection( DB_REPLICA );

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
			switch ( $aArticle['bookshelf']['type'] ) {
				case 'wikipage':
					$oDOMNode = $this->getDOMNodeForWikiPage( $aArticle, $aTemplate, $oTOCList, $aBookMeta, $aLinkMap, $aBookPage );
					break;
				case 'text':
					$oDOMNode = $this->getDOMNodeForText( $aArticle, $aTemplate, $oTOCList, $aBookMeta, $aLinkMap, $aBookPage );
					break;
				case 'tag':
					$oDOMNode = $this->getDOMNodeForTag( $aArticle, $aTemplate, $oTOCList, $aBookMeta, $aLinkMap, $aBookPage );
					break;
				default:
					Hooks::run(
						'BSBookshelfExportUnknownNodeType',
						[ $oDOMNode, $aArticle, &$aTemplate, $oTOCList, $aBookMeta, &$aLinkMap, &$aBookPage ]
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
		// Don't forget to remove the remaining content elements
		foreach ( $aTemplate['content-elements'] as $sKey => $oNode ) {
			if ( !( $oNode instanceof DOMNode ) ) { continue; // Got deleted earlier
			}
			/*if( $oNode->hasChildNodes() ) { //Preserve contents and move them out of the element before removal
				$oDummyNode = $aTemplate['dom']->createTextNode('');
				$oNode->parentNode->insertBefore( $oDummyNode, $oNode );
				foreach( $oNode->childNodes as $oChildNode ) {
					$oDummyNode->appendChild( $oChildNode );
				}
			}*/
			$oNode->parentNode->removeChild( $oNode );
		}

		// Modify internal links
		$oAnchors = $aTemplate['dom']->getElementsByTagName( 'a' );
		foreach ( $oAnchors as $oAnchor ) {
			$sHref  = null;
			$sHref  = $oAnchor->getAttribute( 'href' );
			$sClass = $oAnchor->getAttribute( 'class' );

			if ( empty( $sHref ) ) {
				continue; // Jumplink targets
			}

			$aClasses = explode( ' ', $sClass );
			if ( in_array( 'external', $aClasses ) ) { continue;
			}

			$aHref = parse_url( $sHref );
			if ( !isset( $aHref['path'] ) ) { continue;
			}

			$sPathBasename = basename( $aHref['path'] );
			if ( $sPathBasename == 'index.php' && isset( $aHref['query'] ) ) {
				$aQueryString = [];
				parse_str( $aHref['query'], $aQueryString );

				if ( !isset( $aQueryString['title'] ) ) { continue;
				}

				$sPathBasename = $aQueryString['title'];
			}

			$sPathBasename = str_replace( '_', ' ', $sPathBasename );

			// Seems unneccessary because in $aLinkMap the original name is used
			/*if( in_array( 'mw-redirect', $aClasses ) ) {
				$oTitle = Title::newFromText( $sPathBasename );
				$oArticle = new Article($oTitle);
				$oRTitle = Title::newFromRedirectRecurse(
					$oArticle->fetchContent() // TODO RBV (29.08.12 11:47): FlaggedRevs compat??
				);
				$sPathBasename = $oRTitle->getPrefixedText();
				wfDebugLog( 'BS::Bookshelf', __METHOD__.': Resolving redirect '.$oTitle->getPrefixedText().' to '.$sPathBasename );
			}*/

			if ( !isset( $aLinkMap[$sPathBasename] ) ) { // Do we have a mapping?
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

			$oAnchor->setAttribute( 'href', $aLinkMap[$sPathBasename] );
		}

		$config = \BlueSpice\Services::getInstance()->getConfigFactory()
			->makeConfig( 'bsg' );
		// Set params for PDF creation
		$oCaller->aParams['document-token']   = md5( $oCaller->oRequestedTitle->getPrefixedText() ) . '-' . $oCaller->aParams['oldid'];
		$oCaller->aParams['soap-service-url'] = $config->get(
			'UEModulePDFPdfServiceURL'
		);
		$oCaller->aParams['resources']        = $aTemplate['resources'];
		$oCaller->aParams['attachments']      = '1';

		$oPdfService = new BsPDFServlet( $oCaller->aParams );
		$aResponse['content'] = $oPdfService->createPDF( $aTemplate['dom'] );

		$aResponse['filename'] = sprintf(
			$aResponse['filename'],
			$oCaller->oRequestedTitle->getPrefixedText()
		);

		return $aResponse;
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
		$UEModulePDF = new BsExportModulePDF();
		$oModuleOverviewView = $UEModulePDF->getOverview();

		$oModuleOverviewView->setOption( 'module-title', wfMessage( 'bs-uemodulebookpdf-overview-title' )->text() );
		$oModuleOverviewView->setOption( 'module-description', wfMessage( 'bs-uemodulebookpdf-overview-description' )->text() );
		$oModuleOverviewView->setOption( 'module-bodycontent', wfMessage( 'bs-uemodulebookpdf-overview-bodycontent' )->text() . '<br/>' );

		return $oModuleOverviewView;
	}

	public function getTemplate( $oCaller, $aBookPage, $aBookMeta ) {
		$config = \BlueSpice\Services::getInstance()->getConfigFactory()
			->makeConfig( 'bsg' );

		$sTemplate = $config->get( 'UEModuleBookPDFDefaultTemplate' );

		if ( isset( $aBookMeta['template'] ) && !empty( $aBookMeta['template'] ) ) {
			$sTemplate = $aBookMeta['template'];
		}

		if ( isset( $oCaller->aParams['template'] ) && !empty( $oCaller->aParams['template'] ) ) {
			$sTemplate = $oCaller->aParams['template'];
		}

		if ( isset( $aBookMeta['title'] ) && !empty( $aBookMeta['title'] ) ) {
			$aBookPage['meta']['title'] = $aBookMeta['title'];
		}

		$aTemplate = BsPDFTemplateProvider::getTemplate( [
			'path'     => $config->get( 'UEModuleBookPDFTemplatePath' ),
			'template' => $sTemplate,
			'language' => $oCaller->getUser()->getOption( 'language', 'en' ),
			'meta'     => $aBookPage['meta']
		] );

		return $aTemplate;
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
			$iLevel = count( explode( '.', $aPage['number'] ) );
		}

		// Check wether to include the article's TOC into the book TOC or not
		$bIncludeArticleTOC = false;
		if ( $aPage['toc-ul-element'] instanceof DOMNode ) {
			$config = \BlueSpice\Services::getInstance()->getConfigFactory()
				->makeConfig( 'bsg' );
			if ( $config->get( 'UEModuleBookPDFBookExportTOC' ) == 'article-tocs' ) {
				$bIncludeArticleTOC = true;
			}
			if ( isset( $aBookMeta['bookpdf-export-toc'] ) && $aBookMeta['bookpdf-export-toc'] == 'article-tocs' ) {
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

		return $iLevel;
	}

	public function getDOMNodeForWikiPage( $aArticle, &$aTemplate, $oTOCList, $aBookMeta, &$aLinkMap, &$aBookPage ) {
		$aBS = $aArticle['bookshelf'];

		$aPage = BsPDFPageProvider::getPage( $aArticle );
		// If there is a number set in the data from the client, it overrides
		// the saved one. This can still be overridden by the hook
		if ( isset( $aBS['number'] ) ) {
			$aPage['number'] = trim( $aBS['number'] );
		}

		Hooks::run( 'BSBookshelfExportArticle',
			[ &$aPage, &$aTemplate, &$aBookPage, &$aArticle ] );

		// Add the bookmarks
		$aTemplate['bookmarks-element']->appendChild(
			$aTemplate['dom']->importNode( $aPage['bookmark-element'], true )
		);

		// Save jumplink to article for later link re-writing
		$aLinkMap[$aArticle['title']] = $aPage['bookmark-element']->getAttribute( 'href' );

		$iLevel = $this->buildTOC( $aPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta );

		// Change the headline
		$sDisplayTitle = isset( $aPage['number'] )
			? $aPage['number'] . ' ' . $aArticle['display-title']
			: $aArticle['display-title'];

		$oTitleText = $aPage['firstheading-element']->ownerDocument->createTextNode( $sDisplayTitle );
		$aPage['firstheading-element']->nodeValue = '';
		$aPage['firstheading-element']->replaceChild( $oTitleText, $aPage['firstheading-element']->firstChild );

		$numNode = $aPage['dom']->createElement( 'span' );
		$numNode->setAttribute( 'class', 'bs-chapter-number' );

		$aPage['firstheading-element']->insertBefore( // Prepend
			$numNode,
			$aPage['firstheading-element']->firstChild
		);

		$aPage['firstheading-element']->setAttribute(
			'class', $aPage['firstheading-element']->getAttribute( 'class' ) . ' booklevel-' . $iLevel
		);

		return $aPage['dom']->documentElement;
	}

	public function getDOMNodeForText( $aArticle, &$aTemplate, $oTOCList, $aBookMeta, &$aLinkMap, &$aBookPage ) {
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
	<div>
		<a name="bs-ue-jumpmark-$sId" id="bs-ue-jumpmark-$sId" />
		<h1 class="firstHeading"><span class="bs-chapter-number">$sNumber</span>{$aArticle['display-title']}</h1>
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
			'toc-ul-element' => '' // Dummy
		];
		$iLevel = $this->buildTOC( $aDummyPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta );
		$oHeading->setAttribute( 'class', 'booklevel-' . $iLevel );

		// Add the bookmarks
		$aTemplate['bookmarks-element']->appendChild(
			$aTemplate['dom']->importNode( $aDummyPage['bookmark-element'], true )
		);

		return $oDOM->getElementsByTagName( 'div' )->item( 0 );
	}

	public function getDOMNodeForTag( $aArticle, &$aTemplate, $oTOCList, $aBookMeta, &$aLinkMap, &$aBookPage ) {
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
	<div class="bs-tag-content">
		<a name="bs-ue-jumpmark-$sId" id="bs-ue-jumpmark-$sId" />
		<h1 class="firstHeading"><span class="bs-chapter-number">$sNumber</span>{$aArticle['display-title']}</h1>
		<div class="bodyContent">
		</div>
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

		$oDOMXPath = new DOMXPath( $oDOM );
		$oFirstHeading = $oDOMXPath->query( "//*[contains(@class, 'firstHeading')]" )->item( 0 );
		$oBodyContent  = $oDOMXPath->query( "//*[contains(@class, 'bodyContent')]" )->item( 0 );

		// See class PDFPageProvider for details
		$aDummyPage = [
			'number' => trim( $aArticle['number'] ),
			'dom' => $oDOM,
			'firstheading-element' => $oFirstHeading,
			'bodycontent-element'  => $oBodyContent,
			'bookmarks-dom' => $oBooksmarksDOM,
			'bookmark-element' => $oBooksmarksDOM->getElementsByTagName( 'bookmark' )->item( 0 ),
			'toc-ul-element' => ''
		];

		Hooks::run( 'BSBookshelfExportTag',
			[ &$aDummyPage, &$aArticle, &$aTemplate, $oTOCList, $aBookMeta, &$aLinkMap, &$aBookPage, $oDOMXPath ]
		);

		$iLevel = $this->buildTOC( $aDummyPage, $aTemplate, $oTOCList, $aArticle, $aBookMeta );
		$oFirstHeading->setAttribute( 'class', 'booklevel-' . $iLevel );

		// Add the bookmarks
		$aTemplate['bookmarks-element']->appendChild(
			$aTemplate['dom']->importNode( $aDummyPage['bookmark-element'], true )
		);

		return $aDummyPage['dom']->documentElement;
	}

	// </editor-fold>
}
