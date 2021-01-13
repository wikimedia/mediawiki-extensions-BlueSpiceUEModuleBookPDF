<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BSBookshelfGetBookData;

use BlueSpice\Bookshelf\Hook\BSBookshelfGetBookData;
use BsPDFTemplateProvider;

class AddTemplateMetaData extends BSBookshelfGetBookData {

	protected function doProcess() {
		$this->bookData->bookMetaConfig['bookshelfimage'] = [
			'displayName' => $this->msg( 'bs-uemodulebookpdf-bookshelfimage' )->text()
		];
		$this->bookData->bookMetaConfig['template'] = [
			'displayName' => $this->msg( 'bs-uemodulebookpdf-template' )->text()
		];
		$this->bookData->bookMetaConfig['bookpdf-export-toc'] = [
			'displayName' => $this->msg( 'bs-uemodulebookpdf-pref-bookexporttoc' )->text()
		];
		$aTemplates = BsPDFTemplateProvider::getTemplatesForSelectOptions( [
			'template-path' => $this->getConfig()->get(
				'UEModuleBookPDFTemplatePath'
			)
		] );

		// Make ExtJS ComboBox data format
		$aTemplatesDataSet = [];
		foreach ( $aTemplates as $sName => $Id ) {
			$aTemplatesDataSet[] = [
				'name' => $sName,
				'value' => $Id
			];
		}
		$this->getContext()->getOutput()->addJsConfigVars(
			'bsUEModuleBookPDFTemplates',
			$aTemplatesDataSet
		);
		$this->getContext()->getOutput()->addJsConfigVars(
			'bsUEModuleBookPDFExportTOCOptions',
			[
				[
					'name' => $this->msg( 'bs-uemodulebookpdf-bookexporttoc-only-articles' )
						->text(),
					'value' => 'only-articles'
				],
				[
					'name' => $this->msg( 'bs-uemodulebookpdf-bookexporttoc-article-tocs' )
						->text(),
					'value' => 'article-tocs'
				]
			]
		);
	}

}
