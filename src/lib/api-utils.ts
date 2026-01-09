import { getApiRequest, postApiRequest } from '@wp-plugin-lib';

type ApiRequest = ReturnType< typeof getApiRequest >;

/**
 * API utilities for frequently-used endpoints.
 * Single-use endpoints should be inlined directly in their respective hooks.
 */
export const ApiUtils = {
	source: {
		/**
		 * Get autocomplete suggestions for a source column
		 * Used in: use-search.ts, filter/index.tsx, replace/index.tsx, modify/index.tsx
		 *
		 * @param {string} source - The source type
		 * @param {string} column - The column name
		 * @param {string} value  - The search value
		 */
		complete: ( source: string, column: string, value: string ): ApiRequest =>
			getApiRequest( `search-regex/v1/source/${ source }/complete/${ column }`, { value } ),

		/**
		 * Load a specific row from a source
		 * Used in: use-search.ts, replace/index.tsx
		 *
		 * @param {string}        source - The source type
		 * @param {number|string} rowId  - The row ID
		 */
		loadRow: ( source: string, rowId: number | string ): ApiRequest =>
			getApiRequest( `search-regex/v1/source/${ source }/row/${ rowId }` ),
	},
	plugin: {
		/**
		 * Check API connectivity
		 * Used in: rest-api-status/index.tsx (GET and POST tests)
		 *
		 * @param {string}  url  - The URL to test
		 * @param {boolean} post - Whether to test POST or GET
		 */
		checkApi: ( url: string, post = false ): ApiRequest => {
			const request = post
				? postApiRequest( 'search-regex/v1/plugin/test', { test: 'ping' } )
				: getApiRequest( 'search-regex/v1/plugin/test' );

			request.url = url.startsWith( 'http' ) ? url + request.url : request.url;

			return request;
		},
	},
};
