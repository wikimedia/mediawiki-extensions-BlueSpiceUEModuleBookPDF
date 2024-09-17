bs.ue.ui.plugin.BookPdf = function ( config ) {
	bs.ue.ui.plugin.BookPdf.parent.call( this, config );

	this.config = config || {};
	this.config.title = config.title || '';
	this.dialog = config.dialog || null;

	this.title = this.config.title;
	var bookshelf = $( '.bs-tag-bookshelf' );
	if ( bookshelf.length > 0 ) {
		bookshelf = bookshelf[0];
		var localTitle = undefined;

		if ( $( bookshelf ).attr( 'data-bs-arg-src' ) !== undefined ) {
			localTitle = $( bookshelf ).attr( 'data-bs-arg-src' );
		} else if ( $( bookshelf ).attr( 'data-bs-arg-book' ) !== undefined ) {
			localTitle = $( bookshelf ).attr( 'data-bs-arg-book' );
		}

		if ( localTitle !== undefined ) {
			this.title = localTitle;
		}
	}

	this.template = '';
	this.templateSelect = {};
	this.defaultTemplate = '';

};

OO.inheritClass( bs.ue.ui.plugin.BookPdf, bs.ue.ui.plugin.Plugin );

bs.ue.registry.Plugin.register( 'bookpdf', bs.ue.ui.plugin.BookPdf );

bs.ue.ui.plugin.BookPdf.prototype.getName = function () {
	return 'bookpdf';
};

bs.ue.ui.plugin.BookPdf.prototype.getFavoritePosition = function () {
	return 10;
};

bs.ue.ui.plugin.BookPdf.prototype.getLabel = function () {
	return mw.message( 'bs-uemodulebookpdf-export-dialog-label-module-name' ).text();
};

bs.ue.ui.plugin.BookPdf.prototype.getPanel = function () {
	this.defaultTemplate = mw.config.get( 'bsUEModuleBookPDFDefaultTemplate' );
	var availableTemplates = mw.config.get( 'bsUEModuleBookPDFAvailableTemplates' );
	availableTemplates.sort();

	var templates = [];
	for ( var index = 0; index < availableTemplates.length; index++ ) {
		templates.push(
			{
				label: availableTemplates[index],
				data: availableTemplates[index]
			}
		);
	}

	var modulePanel = new OO.ui.PanelLayout( {
		expanded: false,
		framed: false,
		padded: false,
		$content: ''
	} );

	fieldset = new OO.ui.FieldsetLayout();

	/* Select template */
	this.templateSelect = new OO.ui.DropdownInputWidget( {
		options: templates,
		$overlay: this.dialog ? this.dialog.$overlay : true
	} );

	if ( templates.length > 0 ) {
		this.templateSelect.setValue( this.defaultTemplate );
		this.template = this.defaultTemplate;
	}

	this.templateSelect.on( 'change', this.onChangeTemplate.bind( this ) );

	fieldset.addItems( [
		new OO.ui.FieldLayout(
			this.templateSelect,
			{
				align: 'left',
				label:  mw.message( 'bs-uemodulebookpdf-export-dialog-label-select-template' ).text()
			}
		)
	] );

	modulePanel.$element.append( fieldset.$element );

	return modulePanel;
};

bs.ue.ui.plugin.BookPdf.prototype.getParams = function () {
	var params = {
		module: 'bookpdf',
		title: this.title
	};

	if ( this.template !== this.defaultTemplate ) {
		params.template = this.template;
	}

	return params;
};

bs.ue.ui.plugin.BookPdf.prototype.onChangeTemplate = function () {
	this.template = this.templateSelect.getValue();
}