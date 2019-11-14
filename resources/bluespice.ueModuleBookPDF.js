$(document).on('BSUniversalExportMenuItems', function(event, sender, exportMenuItems) {

	exportMenuItems.push(
		new Ext.menu.Item({
			exportModule: 'bookpdf',
			fileExtension: 'pdf',
			text: mw.message('bs-uemodulebookpdf-btn-export').plain(),
			iconCls: 'icon-page_white_acrobat'
		})
	);
});

$(document).on('BSBookshelfUIManagerPanelInit', function(event, sender) {
	sender.colMainConf.actions.push({
		iconCls: 'bs-extjs-actioncolumn-icon icon-file-pdf',
		glyph: true,
		tooltip: mw.message('bs-uemodulebookpdf-btn-export').plain(),
		handler: function( grid, rowIndex, colIndex ) {
			var record = grid.getStore().getAt( rowIndex );
			grid.getSelectionModel().select( record );

			var url = bs.util.wikiGetlink(
				{
					ue: {
						module: 'bookpdf'
					}
				},
				'Special:UniversalExport/'+record.get('book_prefixedtext')
			);
			window.open( url );
		}
	});
});

$(document).on('BSBookshelfUIMetaGridInit', function(event, sender, metaData, metaDataConfig){
	metaDataConfig.template.editor = Ext.create('BS.UEModuleBookPDF.PDFTemplateCombo', {
		pdfTemplates: mw.config.get('bsUEModuleBookPDFTemplates')
	});
	metaDataConfig['bookpdf-export-toc'].editor = Ext.create('BS.form.SimpleSelectBox', {
		bsData: mw.config.get('bsUEModuleBookPDFExportTOCOptions')
	});
});
