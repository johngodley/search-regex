/* global SearchRegexi10n */
/**
 *
 * @format
 */

/**
 * Internal dependencies
 */
import querystring from 'qs';

const removeEmptyDefaults = item =>
	Object.keys( item )
		.filter( key => item[ key ] || item[ key ] === 0 || item [ key ] === false )
		.reduce( ( newObj, key ) => {
			newObj[ key ] = item[ key ];
			return newObj;
		}, {} );

export const getApiUrl = () => SearchRegexi10n.api && SearchRegexi10n.api.WP_API_root ? SearchRegexi10n.api.WP_API_root : '/wp-json/';
export const setApiUrl = url => SearchRegexi10n.api.WP_API_root = url;
export const getApiNonce = () => SearchRegexi10n.api.WP_API_nonce;
const setApiNonce = nonce => SearchRegexi10n.api.WP_API_nonce = nonce;

const getSearchRegexApi = ( path, params = {} ) => {
	const base = getApiUrl() + 'search-regex/v1/' + path + '/';

	// Some servers dont pass the X-WP-Nonce through to PHP
	params._wpnonce = getApiNonce();

	if ( params && Object.keys( params ).length > 0 ) {
		params = removeEmptyDefaults( params );

		if ( Object.keys( params ).length > 0 ) {
			const querybase =
				base +
				( getApiUrl().indexOf( '?' ) === -1 ? '?' : '&' ) +
				querystring.stringify( params );

			return querybase;
		}
	}

	return base;
};

const getApiHeaders = () => {
	return new Headers( {
		// 'X-WP-Nonce': SearchRegexi10n.api.WP_API_nonce,
		Accept: 'application/json, */*;q=0.1',
	} );
};

const postApiheaders = () => {
	return new Headers( {
		// 'X-WP-Nonce': SearchRegexi10n.api.WP_API_nonce,
		'Content-Type': 'application/json; charset=utf-8',
	} );
};

const apiRequest = url => ( {
	url,
	credentials: 'same-origin',
} );

const deleteApiRequest = ( path, params ) => {
	const query = { ... params };
	const body = {};

	if ( params && params.items ) {
		body.items = params.items;
		delete query.items;
	}

	return {
		headers: postApiheaders(),
		...apiRequest( getSearchRegexApi( path, query ) ),
		method: 'post',
		body: body.items ? JSON.stringify( body ) : '{}',
	};
};

const getApiRequest = ( path, params = {} ) => ( {
	headers: getApiHeaders(),
	...apiRequest( getSearchRegexApi( path, params ) ),
	method: 'get',
} );

const uploadApiRequest = ( path, file ) => {
	const request = { headers: postApiheaders(), ...apiRequest( getSearchRegexApi( path ) ), method: 'post' };

	request.headers.delete( 'Content-Type' );
	request.body = new FormData();
	request.body.append( 'file', file );

	return request;
};

const postApiRequest = ( path, params = {}, query = {} ) => {
	const request = { headers: postApiheaders(), ...apiRequest( getSearchRegexApi( path, query ) ), method: 'post', params };

	request.body = '{}';
	if ( Object.keys( params ).length > 0 ) {
		request.body = JSON.stringify( removeEmptyDefaults( params ) );
	}

	return request;
};

export const SearchRegexApi = {
	setting: {
		get: () => getApiRequest( 'setting' ),
		update: settings => postApiRequest( 'setting', settings ),
	},
	search: {
		get: data => getApiRequest( 'search', data ),
		replace: data => postApiRequest( 'replace', data ),
	},
	source: {
		deleteRow: ( source, rowId ) => postApiRequest( `source/${ source }/${ rowId }/delete` ),
		loadRow: ( source, rowId ) => getApiRequest( `source/${ source }/${ rowId }` ),
		saveRow: ( source, rowId, data ) => postApiRequest( `source/${ source }/${ rowId }`, data ),
	},
	plugin: {
		checkApi: ( url, post = false ) => {
			const request = post ? postApiRequest( 'plugin/test', { test: 'ping' } ) : getApiRequest( 'plugin/test' );

			// Replace normal request URL with the URL to check
			request.url = request.url.replace( getApiUrl(), url ).replace( /[\?&]_wpnonce=[a-f0-9]*/, '' );
			request.url += ( request.url.indexOf( '?' ) === -1 ? '?' : '&' ) + '_wpnonce=' + getApiNonce();

			return request;
		},
	},
};

const getAction = request =>
	request.url.replace( getApiUrl(), '' ).replace( /[\?&]_wpnonce=[a-f0-9]*/, '' ) +
	' ' +
	request.method.toUpperCase();

const getErrorMessage = json => {
	if ( json === 0 ) {
		return 'Admin AJAX returned 0';
	}

	if ( json.message ) {
		return json.message;
	}

	return 'Unknown error ' + json;
};

const getErrorCode = json => {
	if ( json.error_code ) {
		return json.error_code;
	}

	if ( json.data && json.data.error_code ) {
		return json.data.error_code;
	}

	if ( json === 0 ) {
		return 'admin-ajax';
	}

	if ( json.code ) {
		return json.code;
	}

	return 'unknown';
};

export const getApi = request => {
	request.action = getAction( request );

	return fetch( request.url, request )
		.then( data => {
			if ( ! data || ! data.status ) {
				throw { message: 'No data or status object returned in request', code: 0 };
			}

			if ( data.status && data.statusText !== undefined ) {
				request.status = data.status;
				request.statusText = data.statusText;
			}

			if ( data.headers.get( 'x-wp-nonce' ) ) {
				setApiNonce( data.headers.get( 'x-wp-nonce' ) );
			}

			return data.text();
		} )
		.then( text => {
			request.raw = text;

			try {
				const json = JSON.parse( text.replace( /\ufeff/, '' ) );

				if ( request.status && request.status !== 200 ) {
					throw {
						message: getErrorMessage( json ),
						code: getErrorCode( json ),
						request,
						data: json.data ? json.data : null,
					};
				}

				if ( json === 0 ) {
					throw { message: 'Failed to get data', code: 'json-zero' };
				}

				return json;
			} catch ( error ) {
				error.request = request;
				error.code = error.code || error.name;
				throw error;
			}
		} );
};
