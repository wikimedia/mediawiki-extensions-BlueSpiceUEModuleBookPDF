<?php

namespace BlueSpice\UEModuleBookPDF\MetaData;

use BlueSpice\Bookshelf\IMetaDataDescription;
use MediaWiki\Message\Message;

class Template implements IMetaDataDescription {

	/**
	 * @inheritDoc
	 */
	public function getKey(): string {
		return 'template';
	}

	/**
	 * @return Message
	 */
	public function getName(): Message {
		return Message::newFromKey( 'bs-uemodulebookpdf-template' );
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.bluespice.ueModuleBookPDF.meta.template' ];
	}

	/**
	 * @return string
	 */
	public function getJSClassname(): string {
		return 'bs.ue.ui.pages.TemplateMeta';
	}

}
