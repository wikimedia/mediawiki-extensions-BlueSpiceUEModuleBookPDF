<?php

namespace BlueSpice\UEModuleBookPDF;

use BlueSpice\UniversalExport\IExportDialogPlugin;
use IContextSource;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PageHierarchyProvider;
use Title;

class ExportDialogPluginBook implements IExportDialogPlugin {

	/**
	 * @return void
	 */
	public static function factory() {
		return new static();
	}

	/**
	 *
	 * @return array
	 */
	public function getRLModules(): array {
		return [ "ext.bluespice.ueModuleBookPDF.ue-export-dialog-plugin-bookpdf" ];
	}

	/**
	 *
	 * @return array
	 */
	public function getJsConfigVars(): array {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$defaultTemplate = $config->get( 'UEModuleBookPDFDefaultTemplate' );
		$defaultTemplatePath = $config->get( 'UEModuleBookPDFTemplatePath' );

		$availableTemplates = [];
		$dir = opendir( $defaultTemplatePath );
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

		$jsConfigVars = [];
		if ( empty( $availableTemplates ) ) {
			$defaultTemplate = '';
		} else {
			if ( !in_array( $defaultTemplate, $availableTemplates ) ) {
				$defaultTemplate = $availableTemplates[0];
			}
		}

		$jsConfigVars = [
			'bsUEModuleBookPDFDefaultTemplate' => $defaultTemplate,
			'bsUEModuleBookPDFAvailableTemplates' => $availableTemplates
		];

		return $jsConfigVars;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function skip( IContextSource $context ): bool {
		$title = $context->getSkin()->getRelevantTitle();

		if ( $title instanceof Title === false ) {
			return true;
		}

		if ( $title->getContentModel() !== CONTENT_MODEL_WIKITEXT ) {
			return true;
		}

		$toc = null;
		try {
			$php = PageHierarchyProvider::getInstanceForArticle(
				$title->getPrefixedText()
			);
			$toc = $php->getExtendedTOCJSON();
		} catch ( InvalidArgumentException $ex ) {
		}

		if ( !is_object( $toc ) || !property_exists( $toc, 'articleTitle' ) ) {
			return true;
		}

		return false;
	}
}
