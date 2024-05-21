<?php

namespace BlueSpice\UEModuleBookPDF\MetaData;

use BlueSpice\Bookshelf\IMetaDataDescription;
use Message;

class TableOfContent implements IMetaDataDescription {

	/**
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'bookpdf-export-toc';
	}

	/**
	 * @return Message
	 */
	public function getName(): Message {
		return Message::newFromKey( 'bs-uemodulebookpdf-pref-bookexporttoc' );
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.bluespice.ueModuleBookPDF.meta.toc' ];
	}

	/**
	 * @return string
	 */
	public function getJSClassname(): string {
		return 'bs.ue.ui.pages.TableOfContentMeta';
	}

}
