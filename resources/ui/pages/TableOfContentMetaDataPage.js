( function ( mw, $, bs ) {

	bs.util.registerNamespace( 'bs.ue.ui.pages' );

	bs.ue.ui.pages.TableOfContentMeta = function( name, config ) {
		config = config || {};
		bs.ue.ui.pages.TableOfContentMeta.super.call( this, name, config );
	};

	OO.inheritClass( bs.ue.ui.pages.TableOfContentMeta, bs.bookshelf.ui.pages.MetaDataPage );

	bs.ue.ui.pages.TableOfContentMeta.prototype.getOutlineLabel = function () {
		return mw.message( 'bs-uemodulebookpdf-pref-bookexporttoc' ).text();
	};

	bs.ue.ui.pages.TableOfContentMeta.prototype.setup = function () {
		var values = require( './tocoptions.json' );
		var options = [];

		for ( var value in values ) {
			var option = values[ value ].value;
			var item = new OO.ui.MenuOptionWidget( {
				data: option,
				label: mw.message( 'bs-uemodulebookpdf-bookexporttoc-' + option ).text()
			} );
			options.push( item );
		}

		this.inputWidget = new OO.ui.DropdownWidget( {
			menu: {
				items: options
			},
			$overlay: true
		} );
		this.inputWidget.getMenu().selectItemByData( options[0].data );

		var fieldLayout = new OO.ui.FieldLayout( this.inputWidget, {
			align: 'top',
			label: mw.message( 'bs-uemodulebookpdf-pref-bookexporttoc' ).text()
		} );

		this.$element.append( fieldLayout.$element );
	};

	bs.ue.ui.pages.TableOfContentMeta.prototype.getValue = function () {
		return this.inputWidget.getMenu().findSelectedItem().getData();
	};

	bs.ue.ui.pages.TableOfContentMeta.prototype.setValue = function ( value ) {
		this.inputWidget.setValue( value );
	};


} )( mediaWiki, jQuery, blueSpice );