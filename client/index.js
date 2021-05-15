/* global document, SearchRegexi10n */
import 'wp-plugin-lib/polyfill';

/**
 * External dependencies
 */

import React from 'react';
import ReactDOM from 'react-dom';
import i18n from 'i18n-calypso';
import { registerLocale, setDefaultLocale } from 'react-datepicker';

/**
 * Internal dependencies
 */

import App from './app';

const show = ( dom ) => {
	// sigh
	document.querySelector( '.jquery-migrate-deprecation-notice' ) &&
		document.querySelector( '.jquery-migrate-deprecation-notice' ).remove();

	i18n.setLocale( { '': SearchRegexi10n.locale } );
	i18n.addTranslations( SearchRegexi10n.locale.translations );

	const dateLocale = SearchRegexi10n.locale.localeSlug.replace( '_', '' );
	setDefaultLocale( dateLocale );

	ReactDOM.render( <App />, document.getElementById( dom ) );
};

if ( document.querySelector( '#react-ui' ) ) {
	show( 'react-ui' );
}

window.searchregex = SearchRegexi10n.version;
