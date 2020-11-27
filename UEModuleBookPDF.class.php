<?php

/**
 * UEModuleBookPDF extension for BlueSpice
 *
 * Enables BlueSpice to export hierarchical collections of articles to PDF
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://bluespice.com
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    BlueSpiceBookmaker
 * @subpackage UEModuleBookPDF
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

class UEModuleBookPDF extends BsExtensionMW {

	protected function initExt() {
		$this->setHook( 'BSBookshelfBookUI' );
		$this->setHook( 'BSBookshelfBookManager' );
	}

	/**
	 * Adds I18N for 'template' meta. The CellEditor is created in JS
	 * @param SpecialBookshelfBookUI $oSpecialPage
	 * @param OutputPage $oOutputPage
	 * @param object $oData
	 * @return bool Always true to keep hook running
	 */
	public function onBSBookshelfBookUI( $oSpecialPage, $oOutputPage, $oData ) {
		$oData->bookMetaConfig['bookshelfimage'] = [
			'displayName' => wfMessage( 'bs-uemodulebookpdf-bookshelfimage' )->text()
		];
		$oData->bookMetaConfig['template'] = [
			'displayName' => wfMessage( 'bs-uemodulebookpdf-template' )->text()
		];
		$oData->bookMetaConfig['bookpdf-export-toc'] = [
			'displayName' => wfMessage( 'bs-uemodulebookpdf-pref-bookexporttoc' )->text()
		];
		$aTemplates = BsPDFTemplateProvider::getTemplatesForSelectOptions( [
			'template-path' => $this->getConfig()->get(
				'UEModuleBookPDFTemplatePath'
			)
		] );

		// Make ExtJS ComboBox data format
		$aTemplatesDataSet = [];
		foreach ( $aTemplates as $sName => $Id ) {
			$aTemplatesDataSet[] = [
				'name' => $sName,
				'value' => $Id
			];
		}
		$oOutputPage->addJsConfigVars(
			'bsUEModuleBookPDFTemplates',
			$aTemplatesDataSet
		);
		$oOutputPage->addJsConfigVars(
			'bsUEModuleBookPDFExportTOCOptions',
			[
				[
					'name' => wfMessage( 'bs-uemodulebookpdf-bookexporttoc-only-articles' )->text(),
					'value' => 'only-articles'
				],
				[
					'name' => wfMessage( 'bs-uemodulebookpdf-bookexporttoc-article-tocs' )->text(),
					'value' => 'article-tocs'
				]
			]
		);
		return true;
	}

	/**
	 * Adds module dependencies for the ExtJS Manager
	 * @param SpecialBookshelfBookUI $oSpecialPage
	 * @param OutputPage $oOutputPage
	 * @param object $oConfig
	 * @return bool Always true to keep hook running
	 */
	public function onBSBookshelfBookManager( $oSpecialPage, $oOutputPage, $oConfig ) {
		$oConfig->dependencies[] = 'ext.bluespice.ueModuleBookPDF';
		return true;
	}

}
