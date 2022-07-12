/**
 * Internal dependencies
 */

import getPreload from '../../lib/preload';
import { getQuerySearchParams, getDefaultSearch, getSearchFromPreset } from './selector';
import getValidatedSearch from './validate';
import { getPageUrl } from '@wp-plugin-lib';

/** @typedef {import('./type.js').SearchState} SearchState */

/**
 * @returns {SearchState}
 */
export function getInitialSearch() {
	const query = getPageUrl();
	const sources = getPreload( 'sources', [] );
	const search = getValidatedSearch( {
		...getDefaultSearch(),
		...getSearchFromPreset(
			getPreload( 'presets', [] ).find(
				( item ) => item.id === query.preset || item.id === SearchRegexi10n.settings.defaultPreset
			)
		),
		...getQuerySearchParams(),
	} );

	return {
		results: [],
		resultsDirty: false,
		replacing: [],

		search,

		searchDirection: null,

		labels: getPreload( 'labels', [] ),

		requestCount: 0,
		totals: {
			matched_rows: 0,
			matched_phrases: 0,
			rows: 0,
		},
		progress: {},

		status: null,
		showLoading: false,
		isSaving: false,

		sources,

		canCancel: false,

		schema: getPreload( 'schema', [] ),
	};
}
