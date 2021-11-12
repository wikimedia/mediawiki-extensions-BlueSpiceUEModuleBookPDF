<?php

namespace BlueSpice\UEModuleBookPDF\HookHandler;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;

class BlueSpiceDiscovery implements BlueSpiceDiscoveryTemplateDataProviderAfterInit {

	/**
	 *
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->unregister( 'actioncollection/actions', 'ca-bs_export_menu' );
		$registry->register( 'panel/export', 'ca-bs_export_menu' );
	}

}
