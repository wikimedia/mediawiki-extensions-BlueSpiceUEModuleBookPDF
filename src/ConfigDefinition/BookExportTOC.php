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
		return 'bs-bookshelf-pref-BookExportTOC';
	}

	public function getHtmlFormField() {
		return new \HTMLSelectField( $this->makeFormFieldParams() );
	}

	protected function getOptions() {
		return [
			wfMessage( 'bs-bookshelf-bookexporttoc-only-articles' )->text() => 'only-articles',
			wfMessage( 'bs-bookshelf-bookexporttoc-article-tocs' )->text()  => 'article-tocs'
		];
	}
}
