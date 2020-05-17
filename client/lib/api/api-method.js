/**
 * External dependencies
 */
import querystring from 'qs';

/**
 * Internal dependencies
 */
import { getApiUrl, getApiNonce } from './api-url';

const getRequestString = ( path, params = {} ) => {
	const base = getApiUrl() + 'search-regex/v1/' + path + '/';
	const noncedParams = {
		...params,
		_wpnonce: getApiNonce(),
	};

	return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + querystring.stringify( noncedParams );
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
		Accept: 'application/json, */*;q=0.1',
	} );
};

/**
 * Return a request GET object suitable for `fetch`
 * @param {String} path URL path
 * @param {Object} query Query parameters
 */
export const getApiRequest = ( path, query = {} ) => ( {
	headers: getApiHeaders(),
	url: getRequestString( path, query ),
	credentials: 'same-origin',
	method: 'get',
} );

/**
 * Return a request POST object suitable for `fetch`
 * @param {String} path URL path
 * @param {Object} params Body parameters
 * @param {Object} query Query parameters
 * @return {Object} Request object
 */
export const postApiRequest = ( path, params = {}, query = {} ) => {
	const request = {
		headers: postApiheaders(),
		url: getRequestString( path, query ),
		credentials: 'same-origin',
		method: 'post',
		body: '{}',
	};

	if ( Object.keys( params ).length > 0 ) {
		request.body = JSON.stringify( params );
	}

	return request;
};
