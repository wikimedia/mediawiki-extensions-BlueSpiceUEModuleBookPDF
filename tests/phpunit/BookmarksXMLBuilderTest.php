<?php

namespace BlueSpice\UEModuleBookPDF\Test;

use BlueSpice\UEModuleBookPDF\BookmarksXMLBuilder;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

class BookmarksXMLBuilderTest extends TestCase {

	/**
	 * @covers BookmarksXMLBuilder::buildFromFlatBookmarksList
	 * @param DOMElement[] $list
	 * @param string $expectedXMLString
	 * @dataProvider provideBuildFromFlatBookmarksListData
	 */
	public function testBuildFromFlatBookmarksList( $list, $expectedXMLString ) {
		$builder = new BookmarksXMLBuilder();
		$boomarksEl = $builder->buildFromFlatBookmarksList( $list );
		$actualXMLString = $boomarksEl->ownerDocument->saveXML( $boomarksEl );

		$this->assertXmlStringEqualsXmlString( $expectedXMLString, $actualXMLString );
	}

	/**
	 *
	 * @return array
	 */
	public function provideBuildFromFlatBookmarksListData() {
		return [
			'simple-example' => [
				[
					'2' => $this->makeDummyBookmarkEl( 'Some Page 2', '#abc' ),
					'1' => $this->makeDummyBookmarkEl( 'Some Page 1', '#def' ),
					'2.1' => $this->makeDummyBookmarkEl( 'Some Page 2.1', '#ghi' )
				],
'<bookmarks>
  <bookmark name="Some Page 1" href="#def" />
  <bookmark name="Some Page 2" href="#abc">
    <bookmark name="Some Page 2.1" href="#ghi" />
  </bookmark>
</bookmarks>
'
			],
			'complex-example' => [
				[
					'2' => $this->makeDummyBookmarkEl( 'Some Page 2', '#abc' ),
					'1' => $this->makeDummyBookmarkEl( 'Some Page 1', '#def' ),
					'2.1' => $this->makeDummyBookmarkEl( 'Some Page 2.1', '#ghi',
						[ $this->makeDummyBookmarkEl( 'Some Heading 2.1.1', '#jkl' ) ]
					)
				],
'<bookmarks>
  <bookmark name="Some Page 1" href="#def" />
  <bookmark name="Some Page 2" href="#abc">
      <bookmark name="Some Page 2.1" href="#ghi">
          <bookmark name="Some Heading 2.1.1" href="#jkl" />
      </bookmark>
  </bookmark>
</bookmarks>
'
			]
		];
	}

	/**
	 *
	 * @param string $name
	 * @param string $href
	 * @param DOMElement $childEls
	 * @return DOMElement
	 */
	private function makeDummyBookmarkEl( $name, $href, $childEls = [] ) {
		$dom = new DOMDocument();
		$dom->loadXML( '<bookmarks></bookmarks>' );
		$bookmarkEl = $dom->createElement( 'bookmark' );
		$bookmarkEl->setAttribute( 'name', $name );
		$bookmarkEl->setAttribute( 'href', $href );
		foreach ( $childEls as $childNode ) {
			$bookmarkEl->appendChild( $dom->importNode( $childNode ) );
		}
		return $bookmarkEl;
	}

}
