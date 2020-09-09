<?php

namespace BlueSpice\UEModuleBookPDF\Hook\SkinTemplateOutputPageBeforeExec;

use PageHierarchyProvider;

class AddBookExportActionBookPage extends AddBookExportAction {

	/**
	 * @return PageHierarchyProvider
	 */
	protected function getPHP() {
		return PageHierarchyProvider::getInstanceFor(
			$this->skin->getTitle()->getPrefixedText()
		);
	}

	/**
	 * @return string
	 */
	protected function getBookType() {
		if ( $this->skin->getTitle()->getNamespace() === NS_USER ) {
			return 'user_book';
		}

		return 'ns_book';
	}

	/**
	 * @return string
	 */
	protected function getTargetContentModel() {
		return 'book';
	}
}
