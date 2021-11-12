<?php

namespace BlueSpice\UEModuleBookPDF\HookHandler;

use BlueSpice\UniversalExport\ModuleFactory;
use InvalidArgumentException;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use Message;
use PageHierarchyProvider;
use SkinTemplate;
use Title;

class Skin implements SkinTemplateNavigation__UniversalHook {

	/**
	 *
	 * @var ModuleFactory
	 */
	private $moduleFactory = null;

	/**
	 *
	 * @var stdClass
	 */
	private $toc = null;

	/**
	 *
	 * @param ModuleFactory $moduleFactory
	 */
	public function __construct( ModuleFactory $moduleFactory ) {
		$this->moduleFactory = $moduleFactory;
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 * @return void
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$title = $sktemplate->getSkin()->getRelevantTitle();
		if ( $this->skipProcessing( $title ) ) {
			return;
		}
		$module = $this->moduleFactory->newFromName( 'bookpdf' );
		$additional = [
			'title' => $this->toc->articleTitle,
			'book_type' => $this->getBookType(),
		];

		$links['actions'] = array_merge(
			$links['actions'],
			[
				'bs_export_menu' => [
					'id' => 'pdf-book',
					'href' => $module->getExportLink( $sktemplate->getSkin()->getRequest(), $additional ),
					'title' => Message::newFromKey( 'bs-bookshelf-action-export-book' )->text(),
					'text' => Message::newFromKey( 'bs-bookshelf-action-export-book' )->text(),
					'class' => 'bs-ue-export-link',
					'iconClass' => 'icon-file-pdf bs-ue-export-link'
				]
			]
		);
	}

	/**
	 *
	 * @param Title $title
	 * @return bool
	 */
	private function skipProcessing( $title ) {
		if ( $title instanceof Title === false ) {
			return true;
		}
		if ( $title->getContentModel() !== $this->getTargetContentModel() ) {
			return true;
		}
		try {
			$php = $this->getPHP( $title );
			$this->toc = $php->getExtendedTOCJSON();

		} catch ( InvalidArgumentException $ex ) {
		}
		if ( !is_object( $this->toc ) || !property_exists( $this->toc, 'articleTitle' ) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param Title $title
	 * @return PageHierarchyProvider
	 */
	protected function getPHP( $title ) {
		return PageHierarchyProvider::getInstanceForArticle(
			$title->getPrefixedText()
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
	private function getTargetContentModel() {
		return CONTENT_MODEL_WIKITEXT;
	}
}
