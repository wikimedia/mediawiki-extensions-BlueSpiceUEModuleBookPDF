<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$this->out->addModules( 'ext.bluespice.ueModuleBookPDF.contextMenu' );

		$isContentModel = $this->out->getTitle()->getContentModel() === 'book';
		$onBookUI = $this->out->getTitle()->isSpecial( 'BookshelfBookEditor' );
		$onBookManager = $this->out->getTitle()->isSpecial( 'BookshelfBookManager' );

		if ( $isContentModel || $onBookUI || $onBookManager ) {
			$this->out->addModules( 'ext.bluespice.ueModuleBookPDF' );
		}

		return true;
	}

}
