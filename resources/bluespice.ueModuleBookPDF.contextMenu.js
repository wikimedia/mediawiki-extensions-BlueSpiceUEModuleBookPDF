(function( mw, $, bs, d, undefined ){
	$(d).on('BSContextMenuBeforeCreate', function( e, $anchor, items ) {
		var bsTitle = $anchor.data('bs-title');
		if( !bsTitle ) {
			return;
		}

		var title = new mw.Title( bsTitle );
		if( title.getNamespaceId() === bs.ns.NS_BOOK ) {
			items.push({
				iconCls: 'icon-file-pdf',
				text: mw.message('bs-uemodulebookpdf-btn-export').plain(),
				href: bs.util.wikiGetlink(
					{
						ue: {
							module: 'bookpdf'
						}
					},
					'Special:UniversalExport/' + title.getPrefixedText()
				)
			});
		}
	});
})( mediaWiki, jQuery, blueSpice, document );
