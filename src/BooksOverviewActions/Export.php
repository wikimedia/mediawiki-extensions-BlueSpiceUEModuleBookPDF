<?php

namespace BlueSpice\UEModuleBookPDF\BooksOverviewActions;

use BlueSpice\Bookshelf\IBooksOverviewAction;
use MediaWiki\SpecialPage\SpecialPageFactory;
use Message;
use Title;

class Export implements IBooksOverviewAction {

	/**
	 * @var Title
	 */
	private $book = null;

	/**
	 * @var SpecialPageFactory
	 */
	private $specialPageFactory = null;

	/**
	 * @var string
	 */
	private $displayTitle = '';

	/**
	 * @param Title $book
	 * @param SpecialPageFactory $specialPageFactory
	 * @param string $displayTitle
	 */
	public function __construct( Title $book, SpecialPageFactory $specialPageFactory, string $displayTitle ) {
		$this->book = $book;
		$this->specialPageFactory = $specialPageFactory;
		$this->displayTitle = $displayTitle;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'book_pdf';
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return 20;
	}

	/**
	 * @return array
	 */
	public function getClasses(): array {
		return [];
	}

	/**
	 * @return array
	 */
	public function getIconClasses(): array {
		return [ 'bi-file-earmark-pdf-fill' ];
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return new Message( 'bs-uemodulebookpdf-books-overview-page-book-action-export-book-text' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		$titleText = $this->book->getPrefixedText();
		if ( $this->displayTitle !== '' ) {
			$titleText = $this->displayTitle;
		}
		return new Message(
			'bs-uemodulebookpdf-books-overview-page-book-action-export-book-title',
			[ $titleText ]
		);
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$universalExport = $this->specialPageFactory->getPage( 'UniversalExport' );
		if ( !$universalExport ) {
			return '';
		}
		$queryParams['ue[module]'] = 'bookpdf';
		$queryParams['oldid'] = $this->book->getLatestRevID();
		return $universalExport->getPageTitle( $this->book->getPrefixedText() )
			->getLinkURL( array_merge( $queryParams, [] ) );
	}

	/**
	 * @return string
	 */
	public function getRequiredPermission(): string {
		return 'uemodulebookpdf-export';
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [];
	}
}
