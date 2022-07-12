/**
 * External dependencies
 */
import { createRoot } from 'react-dom/client';
import { setDefaultLocale } from 'react-datepicker';

/**
 * Internal dependencies
 */

import App from './app';
import './style.scss';

function show( dom: string ) {
	const element = document.getElementById( dom );
	if ( element ) {
		const root = createRoot( element );

		root.render( <App /> );
	}
}

if ( document.querySelector( '#react-ui' ) ) {
	const migrate = document.querySelector( '.jquery-migrate-deprecation-notice' );

	if ( migrate ) {
		migrate.remove();
	}

	setDefaultLocale( SearchRegexi10n.locale.replace( '_', '' ) );
	show( 'react-ui' );
}

window.searchregex = SearchRegexi10n.version;
