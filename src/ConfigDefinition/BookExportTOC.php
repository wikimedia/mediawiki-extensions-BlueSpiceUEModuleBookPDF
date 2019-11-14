<?php

namespace BlueSpice\UEModuleBookPDF\ConfigDefinition;

class BookExportTOC extends \BlueSpice\ConfigDefinition\ArraySetting {

	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_EXPORT . '/BlueSpiceUEModuleBookPDF',
			static::MAIN_PATH_EXTENSION . '/BlueSpiceUEModuleBookPDF/' . static::FEATURE_EXPORT,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_PRO . '/BlueSpiceUEModuleBookPDF',
		];
	}

	public function getLabelMessageKey() {
		return 'bs-uemodulebookpdf-pref-bookexporttoc';
	}

	public function getHtmlFormField() {
		return new \HTMLSelectField( $this->makeFormFieldParams() );
	}

	protected function getOptions() {
		return [
			wfMessage( 'bs-uemodulebookpdf-bookexporttoc-only-articles' )->text() => 'only-articles',
			wfMessage( 'bs-uemodulebookpdf-bookexporttoc-article-tocs' )->text()  => 'article-tocs'
		];
	}
}
