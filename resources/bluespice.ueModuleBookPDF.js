$( document ).on('BSUniversalExportMenuItems', function(event, sender, exportMenuItems) {

	exportMenuItems.push(
		new Ext.menu.Item({
			exportModule: 'bookpdf',
			fileExtension: 'pdf',
			text: mw.message('bs-uemodulebookpdf-btn-export').plain(),
			iconCls: 'icon-file-pdf'
		})
	);
});

$( document ).on( 'BSBookshelfUIManagerPanelInit', function(event, sender) {
	sender.colMainConf.actions.push({
		iconCls: 'bs-extjs-actioncolumn-icon icon-file-pdf',
		glyph: true,
		tooltip: mw.message('bs-uemodulebookpdf-btn-export').plain(),
		handler: function( grid, rowIndex, colIndex ) {
			var record = grid.getStore().getAt( rowIndex ),
				title = record.get('book_prefixedtext'),
				type = record.get( 'book_type' ),
				location = bs.bookshelf.storageLocationRegistry.lookup( type ),
				params = {
					ue: {
						module: 'bookpdf'
					},
					book_type: record.get( 'book_type' )
				};
			grid.getSelectionModel().select( record );

			if ( location && location.isTitleBased() === false ) {
				params.content = location.getBookPageTextForTitle( title );
			}

			var data = {
				abort: false,
				params: params,
				title: title
			};
			mw.hook( 'bs.bookpdf.exporturl' ).fire( data );
			if ( !data.abort ) {
				var url = bs.util.wikiGetlink(
					params,
					'Special:UniversalExport/' + title
				);


				window.open( url );
			}
		}
	} );
} );

$( document ).on('BSBookshelfUIMetaGridInit', function(event, sender, metaData, metaDataConfig){
	metaDataConfig.template.editor = Ext.create('BS.UEModuleBookPDF.PDFTemplateCombo', {
		pdfTemplates: mw.config.get('bsUEModuleBookPDFTemplates')
	});
	metaDataConfig['bookpdf-export-toc'].editor = Ext.create('BS.form.SimpleSelectBox', {
		bsData: mw.config.get('bsUEModuleBookPDFExportTOCOptions')
	});
});
