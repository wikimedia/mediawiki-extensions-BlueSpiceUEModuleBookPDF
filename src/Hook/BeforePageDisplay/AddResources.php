<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function skipProcessing() {
		if ( !$this->out->getTitle() ) {
			return true;
		}
	}

	protected function doProcess() {
		$this->out->addModules( 'ext.bluespice.ueModuleBookPDF.contextMenu' );

		$title = $this->out->getTitle();
		$isContentModel = $title->getContentModel() === 'book';
		$onBookUI = $title->isSpecial( 'BookshelfBookEditor' );
		$onBookManager = $title->isSpecial( 'BookshelfBookManager' );

		if ( $isContentModel || $onBookUI || $onBookManager ) {
			$this->out->addModules( 'ext.bluespice.ueModuleBookPDF' );
		}

		return true;
	}

}
