Ext.define( 'BS.UEModuleBookPDF.PDFTemplateCombo', {
	extend: 'Ext.form.field.ComboBox',
	queryMode: 'local',
	triggerAction: 'all',
	displayField: 'name',
	valueField: 'value',
	allowBlank: false,
	forceSelection: true,
	
	//Cutsom settings
	pdfTemplates: {},
	
	initComponent: function() {
		this.store = Ext.create('Ext.data.JsonStore', {
			fields: [ 'name', 'value' ],
			data: this.pdfTemplates
		});
		this.callParent(arguments);
	}
});