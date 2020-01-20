<?php

namespace BlueSpice\UEModuleBookPDF;

use DOMDocument;
use DOMElement;

class BookmarksXMLBuilder {

	/**
	 *
	 * @var DOMDocument
	 */
	protected $dom = null;

	/**
	 *
	 * @var DOMElement[]
	 */
	protected $flatList = [];

	/**
	 * Items must be indexed by their hierarchial number
	 *
	 * @param DOMElement[] $list
	 * @return DOMElement
	 */
	public function buildFromFlatBookmarksList( $list ) {
		$this->flatList = $list;
		ksort( $this->flatList );
		$this->initDOM();
		$this->buildTree();

		return $this->dom->documentElement;
	}

	private function initDOM() {
		$this->dom = new DOMDocument();
		$this->dom->loadXML( '<bookmarks></bookmarks>' );
	}

	private function buildTree() {
		$parentEl = $this->dom->documentElement;
		$previousEl = $parentEl;
		$previousLevel = 0;
		foreach ( $this->flatList as $number => $bookmarkEl ) {
			$currentLevel = $this->makeLevel( $number );
			if ( $currentLevel > $previousLevel ) {
				$parentEl = $previousEl;
			}
			if ( $currentLevel < $previousLevel ) {
				$parentEl = $parentEl->parentNode;
			}
			$importedBookmarkEl = $this->dom->importNode( $bookmarkEl, true );
			$parentEl->appendChild( $importedBookmarkEl );

			$previousLevel = $currentLevel;
			$previousEl = $importedBookmarkEl;
		}
	}

	/**
	 *
	 * @param string $number
	 * @return int
	 */
	private function makeLevel( $number ) {
		$parts = explode( '.', $number );
		$level = count( $parts );
		return $level;
	}

}
