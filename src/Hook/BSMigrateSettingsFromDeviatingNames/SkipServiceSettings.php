<?php

namespace BlueSpice\UEModuleBookPDF\Hook\BSMigrateSettingsFromDeviatingNames;

class SkipServiceSettings extends \BlueSpice\Hook\BSMigrateSettingsFromDeviatingNames {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( in_array( $this->oldName, $this->getSkipSettings() ) ) {
			return false;
		}
		return true;
	}

	protected function doProcess() {
		$this->skip = true;
	}

	/**
	 *
	 * @return string[]
	 */
	protected function getSkipSettings() {
		return [
			'MW::UEModuleBookPDF::DefaultTemplate',
			'MW::UEModuleBookPDF::TemplatePath'
		];
	}
}
