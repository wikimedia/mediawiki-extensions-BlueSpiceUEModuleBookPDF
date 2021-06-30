<?php

namespace BlueSpice\UEModuleBookPDF\Hook;

use BlueSpice\Hook;
use BlueSpice\UniversalExport\ExportSpecification;
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

	/** @var ExportSpecification */
	protected $specification = null;

	/**
	 * @param array &$template
	 * @param array &$bookPage
	 * @param array &$articles
	 * @param ExportSpecification $specification
	 * @return bool
	 */
	public static function callback( &$template, &$bookPage, &$articles, $specification ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$template,
			$bookPage,
			$articles,
			$specification
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array &$template
	 * @param array &$bookPage
	 * @param array &$articles
	 * @param ExportSpecification $specification
	 */
	public function __construct(
		$context, $config, &$template, &$bookPage, &$articles, $specification
	) {
		parent::__construct( $context, $config );

		$this->template = &$template;
		$this->bookPage = &$bookPage;
		$this->articles = &$articles;
		$this->specification = $specification;
	}
}
