(function( mw, $, d, undefined ){

	window.onBookshelfViewToolExportBook = function( data ) {
		let currentBook = mw.config.get( 'wgPageName' );
		let selectedItems = findSelectedItems( data );

		let articles = [];
		for ( var index = 0; index < selectedItems.length; index++ ) {
			articles.push( selectedItems[index] );
		}

		let params = {
			'ue[module]': 'bookpdf',
			'ue[articles]': JSON.stringify( articles ),
			'book_type': 'ns_book',
			'oldid': mw.config.get( 'wgRevisionId' )
		};

		let exportPage = mw.Title.newFromText( 'Special:UniversalExport/'+currentBook );
		let exportURL = mw.config.get( 'wgServer' );
		exportURL += exportPage.getUrl( params );
		window.open( exportURL, '_blank' );
	}

	function findSelectedItems( items ) {
		let selectedItems = []
		for ( var index = 0; index < items.length; index++ ) {
			let item = items[index];

			if ( item.hasOwnProperty( 'selected' ) && item.selected === true ) {
				selectedItems.push( item.chapter );
			}

			if ( item.hasOwnProperty( 'children' ) && item.children.length > 0 ) {
				let selectedChildren = findSelectedItems( item.children );
				selectedItems = selectedItems.concat( selectedChildren );
			}
		}
		return selectedItems;
	}
})( mediaWiki, jQuery, document );
