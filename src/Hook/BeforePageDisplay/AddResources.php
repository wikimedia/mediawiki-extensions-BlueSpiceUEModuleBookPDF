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

		return true;
	}

}
