/**
 * Internal dependencies
 */

import { postApiRequest, getApiRequest, uploadApiRequest } from 'wp-plugin-lib/api-fetch/api-method';

/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

const SearchRegexApi = {
	setting: {
		get: () => getApiRequest( 'search-regex/v1/setting' ),
		update: ( settings ) => postApiRequest( 'search-regex/v1/setting', settings ),
	},
	search: {
		get: ( data ) => postApiRequest( 'search-regex/v1/search', data ),
		replace: ( data ) => postApiRequest( 'search-regex/v1/replace', data ),
	},
	preset: {
		save: ( search, name ) => postApiRequest( 'search-regex/v1/preset', { ...search, name } ),
		update: /** @param {PresetValue} preset */ ( preset ) =>
			postApiRequest( `search-regex/v1/preset/${ preset.id }`, preset ),
		delete: ( id ) => postApiRequest( `search-regex/v1/preset/${ id }/delete` ),
		export: () => getApiRequest( 'search-regex/v1/preset', { force: true } ),
		upload: ( file ) => uploadApiRequest( 'search-regex/v1/preset/import', {}, file ),
	},
	source: {
		deleteRow: ( source, rowId ) => postApiRequest( `search-regex/v1/source/${ source }/${ rowId }/delete` ),
		loadRow: ( source, rowId ) => getApiRequest( `search-regex/v1/source/${ source }/${ rowId }` ),
		saveRow: ( source, rowId, data ) => postApiRequest( `search-regex/v1/source/${ source }/${ rowId }`, data ),
		replaceRow: ( source, rowId, data ) =>
			postApiRequest( `search-regex/v1/source/${ source }/${ rowId }/replace`, data ),
	},
	plugin: {
		checkApi: ( url, post = false ) => {
			const request = post
				? postApiRequest( 'search-regex/v1/plugin/test', { test: 'ping' } )
				: getApiRequest( 'search-regex/v1/plugin/test' );

			// Replace normal request URL with the URL to check
			request.url = url + request.url;

			return request;
		},
	},
};

export default SearchRegexApi;
