/**
 * Internal dependencies
 */

import getPreload from 'lib/preload';
import { getQuerySearchParams, getDefaultSearch, getSearchFromPreset } from './selector';
import getValidatedSearch from './validate';
import { getPageUrl } from 'wp-plugin-lib/wordpress-url';

export function getInitialSearch() {
	const query = getPageUrl();
	const sources = getPreload( 'sources', [] );
	const search = getValidatedSearch( {
		...getDefaultSearch(),
		...getSearchFromPreset( getPreload( 'presets', [] ).find( ( item ) => item.id === query.preset ) ),
		...getQuerySearchParams( sources ),
	} );

	return {
		results: [],
		replacements: [],
		replacing: [],

		replaceAll: false,
		replaceCount: 0,
		phraseCount: 0,

		search,

		searchDirection: null,

		requestCount: 0,
		totals: {
			matched_rows: 0,
			matched_phrases: 0,
			rows: 0,
		},
		progress: {},

		status: null,
		showLoading: false,

		sources,
		sourceFlags: getPreload( 'source_flags', [] ),

		rawData: null,

		canCancel: false,
	};
}
