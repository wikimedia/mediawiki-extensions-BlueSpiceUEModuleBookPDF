<?php

namespace BlueSpice\UEModuleBookPDF\Hook;

use BlueSpice\UniversalExport\ExportSpecification;

interface BSBookshelfExportBeforeArticlesHook {
	/**
	 *
	 * @param array &$template
	 * @param array &$bookPage
	 * @param array &$articles
	 * @param ExportSpecification $specification
	 *
	 * @return void
	 */
	public function onBSBookshelfExportBeforeArticles(
		array &$template, array &$bookPage, array &$articles, ExportSpecification $specification
	): void;
}
