/**
 * Internal dependencies
 */

import getPreload from 'lib/preload';
import { getQuerySearchParams, getDefaultSearch, applyTagsToSearch } from './selector';
import getValidatedSearch from './validate';
import { getPageUrl } from 'wp-plugin-lib/wordpress-url';

function getInitialPreset() {
	const query = getPageUrl();
	const preset = getPreload( 'presets', [] ).find( ( item ) => item.id === query.preset );

	if ( preset ) {
		return preset.search;
	}

	return {};
}

function getTagValues( searchPhrase ) {
	const query = getPageUrl();
	const preset = getPreload( 'presets', [] ).find( ( item ) => item.id === query.preset );
	const tagValues = {};

	if ( preset?.tags ) {
		for ( let index = 0; index < preset.tags.length; index++ ) {
			for ( let subIndex = 0; subIndex < 10; subIndex++ ) {
				const searchName = `search-${ preset.tags[ index ].name.toLowerCase() }-${ subIndex }`;

				if ( query[ searchName ] ) {
					tagValues[ `search-${ preset.tags[ index ].name }-${ subIndex }` ] = query[ searchName ];
				} else {
					break;
				}
			}
		}
	}

	return {
		tagged: {
			searchPhrase:
				Object.keys( tagValues ).length > 0 ? applyTagsToSearch( { searchPhrase }, 'search', tagValues ).searchPhrase : '',
			replacement: '',
		},
		tagValues,
	};
}

export function getInitialSearch() {
	const sources = getPreload( 'sources', [] );
	const search = getValidatedSearch( {
		...getDefaultSearch(),
		...getInitialPreset(),
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

		...getTagValues( search.searchPhrase ),

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
