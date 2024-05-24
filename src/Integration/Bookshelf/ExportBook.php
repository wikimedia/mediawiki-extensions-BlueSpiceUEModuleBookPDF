<?php

namespace BlueSpice\UEModuleBookPDF\Integration\Bookshelf;

use BlueSpice\Bookshelf\IBookViewTool;

class ExportBook implements IBookViewTool {

	/**
	 * @return string
	 */
	public function getType(): string {
		return IBookViewTool::TYPE_BUTTON;
	}

	/**
	 * TODO: use own message key
	 * @return string
	 */
	public function getLabelMsgKey(): string {
		return 'bs-uemodulebookpdf-books-overview-page-book-action-export-book-text';
	}

	/**
	 * @return string
	 */
	public function getCallback(): string {
		return 'onBookshelfViewToolExportBook';
	}

	/**
	 * @return array
	 */
	public function getClasses(): array {
		return [ 'bookshelf-view-tool-uemodule-bookpdf' ];
	}

	/**
	 * @return string
	 */
	public function getSlot(): string {
		return IBookViewTool::SLOT_RIGHT;
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return 10;
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.bluespice.ueModuleBookPDF' ];
	}

	/**
	 * @return string
	 */
	public function getRequiredPermission(): string {
		return 'uemodulebookpdf-export';
	}

	/**
	 * @return bool
	 */
	public function requireSelectableTree(): bool {
		return true;
	}
}
