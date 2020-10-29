<?php

namespace BlueSpice\UEModuleBookPDF;

use DOMDocument;
use DOMElement;

class BookmarksXMLBuilder {

	/**
	 *
	 * @var DOMDocument
	 */
	private $dom = null;

	/**
	 *
	 * @var DOMElement[]
	 */
	private $flatList = [];

	/**
	 *
	 * @var array
	 */
	private $tree = [];

	/**
	 *
	 * @param array $tree Nested tree structure derived from
	 * \PageHierarchyProvider::getExtendedTOCJSON
	 */
	public function __construct( $tree ) {
		$this->tree = $tree;
	}

	/**
	 * Items must be indexed by their hierarchial number
	 *
	 * @param DOMElement[] $list
	 * @return DOMElement
	 */
	public function buildFromFlatBookmarksList( $list ) {
		$this->flatList = $list;
		$this->normalizeList();
		$this->initDOM();
		$this->fillGapsInList();
		ksort( $this->flatList, SORT_NATURAL );
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
			$number = trim( $number, '.' );
			$currentLevel = $this->makeLevel( $number );
			if ( $currentLevel > $previousLevel ) {
				$parentEl = $previousEl;
			}
			if ( $currentLevel < $previousLevel ) {
				$diff = $previousLevel - $currentLevel;
				for ( $i = 0; $i < $diff; $i++ ) {
					$parentEl = $parentEl->parentNode;
				}
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

	private function fillGapsInList() {
		foreach ( $this->flatList as $number => $bookmarkElement ) {
			$parts = explode( '.', trim( $number, '.' ) );
			while ( count( $parts ) > 1 ) {
				array_pop( $parts );
				$parentNumber = implode( '.', $parts ) . '.';
				if ( !isset( $this->flatList[$parentNumber] ) ) {
					$this->addDummyParentToFlatList( $parentNumber );
				}
			}
		}
	}

	/**
	 *
	 * @param string $parentNumber
	 */
	private function addDummyParentToFlatList( $parentNumber ) {
		$treeNode = $this->findNumberInTree( $parentNumber, $this->tree );
		$text = $treeNode['text'];

		$dummyBookmarkElement = $this->dom->createElement( 'bookmark' );
		$dummyBookmarkElement->setAttribute( 'name', $text );
		$dummyBookmarkElement->setAttribute( 'href', '#' );

		$this->flatList[$parentNumber] = $dummyBookmarkElement;
	}

	/**
	 *
	 * @param string $id
	 * @param array $treeNode
	 * @return array
	 */
	private function findNumberInTree( $id, $treeNode ) {
		$id = trim( $id, '.' );
		foreach ( $treeNode['children'] as $childNode ) {
			if ( isset( $childNode['id'] ) && $childNode['id'] === $id ) {
				return $childNode;
			}
			$recursiveChildNode = $this->findNumberInTree( $id, $childNode );
			if ( !empty( $recursiveChildNode ) ) {
				return $recursiveChildNode;
			}
		}
		return [];
	}

	private function normalizeList() {
		$normalizedList = [];
		foreach ( $this->flatList as $number => $bookmarkEl ) {
			// In some cases the number is not set as "1.1.1." but as "1.1.1" with a tailing dot.
			$normalizedNumber = trim( $number, '.' ) . '.';
			$normalizedList[$normalizedNumber] = $bookmarkEl;
		}
		$this->flatList = $normalizedList;
	}

}
