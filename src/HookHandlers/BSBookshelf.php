<?php

namespace BlueSpice\UEModuleBookPDF\HookHandlers;

use BlueSpice\Bookshelf\Hook\BSBookshelfBooksOverviewBeforeSetBookActions;
use BlueSpice\UEModuleBookPDF\BooksOverviewActions\Export;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;

class BSBookshelf implements BSBookshelfBooksOverviewBeforeSetBookActions {

	/**
	 * @var SpecialPageFactory
	 */
	private $specialPageFactory = null;

	/**
	 * @param SpecialPageFactory $specialPageFactory
	 */
	public function __construct( SpecialPageFactory $specialPageFactory ) {
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @param array &$actions
	 * @param Title $book
	 * @param string $displayTitle
	 * @return void
	 */
	public function onBSBookshelfBooksOverviewBeforeSetBookActions(
		array &$actions, Title $book, string $displayTitle
	): void {
		$actions['book_pdf'] = new Export( $book, $this->specialPageFactory, $displayTitle );
	}

}
