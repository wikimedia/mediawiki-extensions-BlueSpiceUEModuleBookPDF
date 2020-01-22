<?php

namespace BlueSpice\UEModuleBookPDF\Test;

use PHPUnit\Framework\TestCase;
use BlueSpice\UEModuleBookPDF\BookmarksXMLBuilder;
use DOMDocument;
use DOMElement;

class BookmarksXMLBuilderTest extends TestCase {

	/**
	 * @covers BookmarksXMLBuilder::buildFromFlatBookmarksList
	 * @param DOMElement[] $list
	 * @param string $expectedXMLString
	 * @param array $dummyTree
	 * @dataProvider provideBuildFromFlatBookmarksListData
	 */
	public function testBuildFromFlatBookmarksList( $list, $expectedXMLString, $dummyTree ) {
		$builder = new BookmarksXMLBuilder( $dummyTree );
		$boomarksEl = $builder->buildFromFlatBookmarksList( $list );
		$actualXMLString = $boomarksEl->ownerDocument->saveXML( $boomarksEl );

		$this->assertXmlStringEqualsXmlString( $expectedXMLString, $actualXMLString );
	}

	/**
	 *
	 * @return array
	 */
	public function provideBuildFromFlatBookmarksListData() {
		$dummyTree = [
			'text' => 'Text',
			'children' => [
				[
					'text' => 'Some Page 1',
					'id' => '1',
					'children' => [
						[
							'text' => 'Some Page 1.1',
							'id' => '1.1',
							'children' => []
						]
					]
				],
				[
					'text' => 'Some Page 2',
					'id' => '2',
					'children' => [
						[
							'text' => 'Some Page 2.1',
							'id' => '2.1',
							'children' => []
						],
						[
							'text' => 'Some Page 2.2',
							'id' => '2.2',
							'children' => [
								[
									'text' => 'Some Page 2.2.1',
									'id' => '2.2.1',
									'children' => []
								]
							]
						]
					]
				]
			]
		];

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
',
				$dummyTree
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
',
				$dummyTree
			],
			'gap-example' => [
				[
					'1.1' => $this->makeDummyBookmarkEl( 'Some Page 1.1', '#def' ),
					'2' => $this->makeDummyBookmarkEl( 'Some Page 2', '#abc' ),
					'2.2.1' => $this->makeDummyBookmarkEl( 'Some Page 2.2.1', '#ghi',
						[ $this->makeDummyBookmarkEl( 'Some Heading 2.2.1.1', '#jkl' ) ]
					)
				],
'<bookmarks>
  <bookmark name="Some Page 1" href="#">
    <bookmark name="Some Page 1.1" href="#def" />
  </bookmark>
  <bookmark name="Some Page 2" href="#abc">
    <bookmark name="Some Page 2.2" href="#">
      <bookmark name="Some Page 2.2.1" href="#ghi">
        <bookmark name="Some Heading 2.2.1.1" href="#jkl" />
      </bookmark>
    </bookmark>
  </bookmark>
</bookmarks>
',
				$dummyTree
			],
			'unnormalized-list-example' => [
				[
					'2.' => $this->makeDummyBookmarkEl( 'Some Page 2', '#abc' ),
					'1' => $this->makeDummyBookmarkEl( 'Some Page 1', '#def' ),
					'2.1.' => $this->makeDummyBookmarkEl( 'Some Page 2.1', '#ghi' )
				],
'<bookmarks>
  <bookmark name="Some Page 1" href="#def" />
  <bookmark name="Some Page 2" href="#abc">
    <bookmark name="Some Page 2.1" href="#ghi" />
  </bookmark>
</bookmarks>
',
				$dummyTree
			],
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
