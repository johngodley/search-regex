/* global document, SearchRegexi10n */
import 'wp-plugin-lib/polyfill';

/**
 * External dependencies
 */

import React from 'react';
import ReactDOM from 'react-dom';
import i18n from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */

import App from './app';

const show = dom => {
	i18n.setLocale( { '': { localeSlug: SearchRegexi10n.localeSlug } } );
	i18n.addTranslations( SearchRegexi10n.locale );

	ReactDOM.render( <App />, document.getElementById( dom ) );
};

if ( document.querySelector( '#react-ui' ) ) {
	show( 'react-ui' );
}

window.searchregex = SearchRegexi10n.version;
