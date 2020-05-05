/* global SearchRegexi10n */
/**
 * Internal dependencies
 */

import * as qs from 'qs';

const ALLOWED_PAGES = [ 'search', 'options', 'support' ];

export function setPageUrl( query, defaults ) {
	const url = getWordPressUrl( query, defaults );

	if ( document.location.search !== url ) {
		history.pushState( {}, null, url );
	}
}

export function getPageUrl( query ) {
	return qs.parse( query ? query.slice( 1 ) : document.location.search.slice( 1 ) );
}

export function getWordPressUrl( query, defaults, url ) {
	const existing = getPageUrl( url );

	for ( const param in query ) {
		if ( query[ param ] && defaults[ param ] !== query[ param ] ) {
			existing[ param.toLowerCase() ] = query[ param ];
		} else if ( defaults[ param ] === query[ param ] ) {
			delete existing[ param.toLowerCase() ];
		}
	}

	return '?' + qs.stringify( existing );
}

export function getPluginPage( url ) {
	const params = getPageUrl( url );

	if ( ALLOWED_PAGES.indexOf( params.sub ) !== -1 ) {
		return params.sub;
	}

	return 'search';
}
