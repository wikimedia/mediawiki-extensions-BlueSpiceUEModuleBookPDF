<?php

namespace BlueSpice\UEModuleBookPDF\Hook\ChameleonSkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\ChameleonSkinTemplateOutputPageBeforeExec;
use BlueSpice\UniversalExport\ModuleFactory;
use InvalidArgumentException;
use Message;
use MWException;
use PageHierarchyProvider;

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
		/** @var ModuleFactory $moduleFactory */
		$moduleFactory = $this->getServices()->getService(
			'BSUniversalExportModuleFactory'
		);
		$module = $moduleFactory->newFromName( 'bookpdf' );
		$additional = [
			'title' => $this->toc->articleTitle,
			'book_type' => $this->getBookType(),
		];

		$this->template->data['bs_export_menu'][] = [
			'id' => 'pdf-subpages',
			'href' => $module->getExportLink( $this->skin->getRequest(), $additional ),
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
