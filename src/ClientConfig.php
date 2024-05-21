<?php

namespace BlueSpice\UEModuleBookPDF;

use MediaWiki\MediaWikiServices;

class ClientConfig {

	/**
	 *
	 * @return array
	 */
	public static function getBookTemplates() {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$defaultTemplate = $config->get( 'UEModuleBookPDFDefaultTemplate' );
		$defaultTemplatePath = $config->get( 'UEModuleBookPDFTemplatePath' );

		$availableTemplates = [];
		$dir = opendir( $defaultTemplatePath );
		if ( $dir ) {
			$subDir = readdir( $dir );
			while ( $subDir !== false ) {
				if ( in_array( $subDir, [ '.', '..', 'common' ] ) ) {
					$subDir = readdir( $dir );
					continue;
				}

				if ( !is_dir( "{$defaultTemplatePath}/{$subDir}" ) ) {
					$subDir = readdir( $dir );
					continue;
				}

				if ( file_exists( "{$defaultTemplatePath}/{$subDir}/template.php" ) ) {
					$availableTemplates[] = $subDir;
				}

				$subDir = readdir( $dir );
			}
		}

		if ( empty( $availableTemplates ) ) {
			$defaultTemplate = '';
		} else {
			if ( !in_array( $defaultTemplate, $availableTemplates ) ) {
				$defaultTemplate = $availableTemplates[0];
			}
		}
		return [
			'defaultTemplate' => $defaultTemplate,
			'availableTemplates' => $availableTemplates
		];
	}

	/**
	 *
	 * @return array
	 */
	public static function getTOCOptions() {
		return [
			[
				'value' => 'only-articles'
			],
			[
				'value' => 'article-tocs'
			]
		];
	}
}
