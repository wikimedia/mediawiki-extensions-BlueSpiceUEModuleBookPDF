<?php
/**
 * This is the main description file for the template. It contains all
 * information necessary to load and process the template.
 */

$sCommonDir = '../../../../BlueSpiceUEModulePDF/data/PDFTemplates/common';

return array(

	/* A brief description. This information may be used in the user interface */
	'info' => array(
		'name'      => 'BlueSpice Book',
		'author'    => 'Hallo Welt!',
		'copyright' => 'Hallo Welt! GmbH',
		'url'       => 'http://www.hallowelt.com',
		'description'      => 'This is the default BlueSpice PDF Template'
	),

	/**
	 * The following resources are used in the conversion from xhtml to PDF.
	 * You may reference them in your template files
	 */
	'resources' => array(
		'ATTACHMENT' => array(), //Some extra attachments to be included in every eport file
		'STYLESHEET' => array(
			$sCommonDir . '/stylesheets/page.css',
			$sCommonDir . '/stylesheets/mediawiki.css',
			$sCommonDir . '/stylesheets/tables.css',
			'stylesheets/styles.css',
			$sCommonDir . '/stylesheets/geshi-php.css',
			$sCommonDir . '/stylesheets/fonts.css',
			$sCommonDir . '/fonts/DejaVuSans.ttf',
			$sCommonDir . '/fonts/DejaVuSans-Bold.ttf',
			$sCommonDir . '/fonts/DejaVuSans-Oblique.ttf',
			$sCommonDir . '/fonts/DejaVuSans-BoldOblique.ttf',
			$sCommonDir . '/fonts/DejaVuSansMono.ttf',
			$sCommonDir . '/fonts/DejaVuSansMono-Bold.ttf',
			$sCommonDir . '/fonts/DejaVuSansMono-Oblique.ttf',
			$sCommonDir . '/fonts/DejaVuSansMono-BoldOblique.ttf'
		),
		'IMAGE' => array(
			'images/bs-page-background.png',
			'images/bs-header.jpg',
			'images/bs-cover.jpg'
		)
	),

	/**
	 * Here you can define messages for internationalization of your template.
	 */
	'messages' => array(
		'en' => array(
			'desc'        => 'This is the default PDFTemplate of BlueSpice for single article export.',
			'exportdate'  => 'Export date:',
			'page'        => 'Page ',
			'of'          => ' of ',
			'disclaimer'  => 'This document was created with BlueSpice'
		),
		'de' => array(
			'desc'        => 'Dies ist das Standard-PDFTemplate von BlueSpice für den Export einzelner Artikel.',
			'exportdate'  => 'Ausgabe:',
			'page'        => 'Seite ',
			'of'          => ' von ',
			'disclaimer'  => 'Dieses Dokument wurde erzeugt mit BlueSpice'),
		'de-formal' => array(
			'desc'        => 'Dies ist das Standard-PDFTemplate von BlueSpice für den Export einzelner Artikel.',
			'exportdate'  => 'Ausgabe:',
			'page'        => 'Seite ',
			'of'          => ' von ',
			'disclaimer'  => 'Dieses Dokument wurde erzeugt mit BlueSpice'),
	)
);