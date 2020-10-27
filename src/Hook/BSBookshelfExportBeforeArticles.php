<?php

namespace BlueSpice\UEModuleBookPDF\Hook;

use BlueSpice\Hook;
use Config;
use IContextSource;

abstract class BSBookshelfExportBeforeArticles extends Hook {
	/**
	 *
	 * @var array
	 */
	protected $template = null;
	/**
	 *
	 * @var array
	 */
	protected $bookPage = null;
	/**
	 *
	 * @var array
	 */
	protected $articles = null;

	/**
	 * @param array &$template
	 * @param array &$bookPage
	 * @param array &$articles
	 * @return bool
	 */
	public static function callback( &$template, &$bookPage, &$articles ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$template,
			$bookPage,
			$articles
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array &$template
	 * @param array &$bookPage
	 * @param array &$articles
	 */
	public function __construct( $context, $config, &$template, &$bookPage, &$articles ) {
		parent::__construct( $context, $config );

		$this->template = &$template;
		$this->bookPage = &$bookPage;
		$this->articles = &$articles;
	}
}
