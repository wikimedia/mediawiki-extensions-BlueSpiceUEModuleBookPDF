<?php

namespace BlueSpice\UEModuleBookPDF\Hook\ChameleonSkinTemplateOutputPageBeforeExec;

use BlueSpice\Calumma\Hook\ChameleonSkinTemplateOutputPageBeforeExec;
use InvalidArgumentException;
use Message;
use MWException;
use PageHierarchyProvider;
use SpecialPage;

class AddBookExportAction extends ChameleonSkinTemplateOutputPageBeforeExec {
	/** @var array */
	private $toc;

	/**
	 * @inheritDoc
	 */
	public function __construct( $context, $config, &$skin, &$template ) {
		parent::__construct( $context, $config, $skin, $template );

		try {
			$php = $this->getPHP();
			$this->toc = $php->getExtendedTOCJSON();

		} catch ( InvalidArgumentException $ex ) {
		}
	}

	/**
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( $this->skin->getTitle()->getContentModel() !== $this->getTargetContentModel() ) {
			return true;
		}

		if ( !is_object( $this->toc ) || !property_exists( $this->toc, 'articleTitle' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @throws MWException
	 */
	protected function doProcess() {
		$sp = SpecialPage::getTitleFor( 'UniversalExport', $this->toc->articleTitle );
		$params = [
			'ue[module]' => 'bookpdf',
			'book_type' => $this->getBookType(),
		];
		$this->template->data['bs_export_menu'][] = [
			'id' => 'pdf-subpages',
			'href' => $sp->getLinkUrl( $params ),
			'title' => Message::newFromKey( 'bs-bookshelf-action-export-book' )->text(),
			'text' => Message::newFromKey( 'bs-bookshelf-action-export-book' )->text(),
			'class' => 'bs-ue-export-link',
			'iconClass' => 'icon-file-pdf bs-ue-export-link'
		];
	}

	/**
	 * @return PageHierarchyProvider
	 */
	protected function getPHP() {
		return PageHierarchyProvider::getInstanceForArticle(
			$this->skin->getTitle()->getPrefixedText()
		);
	}

	/**
	 * @return string
	 */
	protected function getBookType() {
		return 'ns_book';
	}

	/**
	 * @return string
	 */
	protected function getTargetContentModel() {
		return CONTENT_MODEL_WIKITEXT;
	}
}
