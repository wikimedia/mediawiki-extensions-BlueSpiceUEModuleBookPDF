<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$this->out->addModules( 'ext.bluespice.ueModuleBookPDF.contextMenu' );

		$onBookUI = $this->out->getTitle()->isSpecial( 'BookshelfBookUI' );
		$onBookManager = $this->out->getTitle()->isSpecial( 'BookshelfBookManager' );

		if ( $onBookUI || $onBookManager ) {
			$this->out->addModules( 'ext.bluespice.ueModuleBookPDF' );
		}

		return true;
	}

}
