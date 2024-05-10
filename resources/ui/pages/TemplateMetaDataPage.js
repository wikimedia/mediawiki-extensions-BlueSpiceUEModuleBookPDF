( function ( mw, $, bs ) {

	bs.util.registerNamespace( 'bs.ue.ui.pages' );

	bs.ue.ui.pages.TemplateMeta = function( name, config ) {
		config = config || {};
		bs.ue.ui.pages.TemplateMeta.super.call( this, name, config );
	};

	OO.inheritClass( bs.ue.ui.pages.TemplateMeta, bs.bookshelf.ui.pages.MetaDataPage );

	bs.ue.ui.pages.TemplateMeta.prototype.getOutlineLabel = function () {
		return mw.message( 'bs-uemodulebookpdf-template' ).text();
	};

	bs.ue.ui.pages.TemplateMeta.prototype.setup = function () {
		var values = require( './templatedata.json' );
		var availableTemplates = values.availableTemplates;
		var defaultTemplate = values.defaultTemplate;
		var options = [];
		if ( availableTemplates.length > 1 ) {
			for ( var entry in availableTemplates ) {
				var item = new OO.ui.MenuOptionWidget( {
					data: encodeURI( availableTemplates[ entry ] ),
					label: availableTemplates[ entry ]
				} );
				options.push( item );
			}
		} else {
			var item = new OO.ui.MenuOptionWidget( {
				data: encodeURI( defaultTemplate ),
				label: defaultTemplate
			} );
			options.push( item );
		}

		this.inputWidget = new OO.ui.DropdownWidget( {
			menu: {
				items: options
			},
			$overlay: true
		} );

		if ( availableTemplates.length === 1 ) {
			this.inputWidget.getMenu().selectItemByData( options[0].data );
			this.inputWidget.setDisabled( true );
		}

		var fieldLayout = new OO.ui.FieldLayout( this.inputWidget, {
			align: 'top',
			label: mw.message( 'bs-uemodulebookpdf-template' ).text()
		} );

		this.$element.append( fieldLayout.$element );
	};

	bs.ue.ui.pages.TemplateMeta.prototype.getValue = function () {
		return this.inputWidget.getMenu().findSelectedItem().getData();
	};

	bs.ue.ui.pages.TemplateMeta.prototype.setValue = function ( value ) {
		this.inputWidget.setValue( value );
	};


} )( mediaWiki, jQuery, blueSpice );