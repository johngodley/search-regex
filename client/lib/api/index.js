/* global SearchRegexi10n */

/**
 * Internal dependencies
 */

import { getApiUrl, getApiNonce } from './api-url';
import { postApiRequest, getApiRequest } from './api-method';

export const SearchRegexApi = {
	setting: {
		get: () => getApiRequest( 'setting' ),
		update: settings => postApiRequest( 'setting', settings ),
	},
	search: {
		get: data => postApiRequest( 'search', data ),
		replace: data => postApiRequest( 'replace', data ),
	},
	source: {
		deleteRow: ( source, rowId ) => postApiRequest( `source/${ source }/${ rowId }/delete` ),
		loadRow: ( source, rowId ) => postApiRequest( `source/${ source }/${ rowId }` ),
		saveRow: ( source, rowId, data ) => postApiRequest( `source/${ source }/${ rowId }`, data ),
		replaceRow: ( source, rowId, data ) => postApiRequest( `source/${ source }/${ rowId }/replace`, data ),
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

export * from './api-request';
