<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BSBookshelfBookManager;

use BlueSpice\Bookshelf\Hook\BSBookshelfBookManager;

class AddDependencies extends BSBookshelfBookManager {

	protected function doProcess() {
		$this->configVars->dependencies[] = 'ext.bluespice.ueModuleBookPDF';
	}

}
