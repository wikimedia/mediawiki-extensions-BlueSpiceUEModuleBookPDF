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
									'children' => [
										[
											'text' => 'Some Page 2.2.1.1',
											'id' => '2.2.1.1',
											'children' => []
										]
									]
								]
							]
						]
					]
					],
					[ 'text' => 'Some Page 3', 'id' => '3', 'children' => [] ],
					[ 'text' => 'Some Page 4', 'id' => '4', 'children' => [] ],
					[ 'text' => 'Some Page 5', 'id' => '5', 'children' => [] ],
					[ 'text' => 'Some Page 6', 'id' => '6', 'children' => [] ],
					[ 'text' => 'Some Page 7', 'id' => '7', 'children' => [] ],
					[ 'text' => 'Some Page 8', 'id' => '8', 'children' => [] ],
					[ 'text' => 'Some Page 9', 'id' => '9', 'children' => [] ],
					[ 'text' => 'Some Page 10', 'id' => '10', 'children' => [] ],
					[ 'text' => 'Some Page 11', 'id' => '11', 'children' => [] ],
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
			'natsort-issue-ERM21412' => [
				[
					'1.' => $this->makeDummyBookmarkEl( 'Some Page 1', '#abc' ),
					'2.1' => $this->makeDummyBookmarkEl( 'Some Page 2.1', '#def' ),
					'10' => $this->makeDummyBookmarkEl( 'Some Page 10', '#ghi' ),
					'11' => $this->makeDummyBookmarkEl( 'Some Page 11', '#jkl' ),
					'2.2.1.1' => $this->makeDummyBookmarkEl( 'Some Page 2.2.1.1', '#mno' )
				],
'<bookmarks>
  <bookmark name="Some Page 1" href="#abc" />
  <bookmark name="Some Page 2" href="#">
    <bookmark name="Some Page 2.1" href="#def" />
    <bookmark name="Some Page 2.2" href="#">
        <bookmark name="Some Page 2.2.1" href="#">
            <bookmark name="Some Page 2.2.1.1" href="#mno" />
        </bookmark>
    </bookmark>
  </bookmark>
  <bookmark name="Some Page 10" href="#ghi" />
  <bookmark name="Some Page 11" href="#jkl" />
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
