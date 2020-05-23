/**
 * Internal dependencies
 */

import { getPageUrl } from 'lib/wordpress-url';
import { getAllPostTypes } from 'lib/sources';
import getPreload from 'lib/preload';
import getValidatedSearch from 'state/search/validate';

function addPostTypes( sources, allPostTypes ) {
	if ( sources.indexOf( 'posts' ) !== -1 ) {
		return sources.filter( item => allPostTypes.indexOf( item ) === -1 ).concat( allPostTypes );
	}

	return sources;
}

function getInitialSearchParams( sources ) {
	const query = getPageUrl();
	const allPostTypes = getAllPostTypes( sources );
	const initialSources = query.source ? addPostTypes( query.source, allPostTypes ) : [ 'post', 'page' ];

	return getValidatedSearch( {
		searchPhrase: query.searchphrase ? query.searchphrase : '',
		searchFlags: query.searchflags ? query.searchflags : [ 'case' ],

		source: initialSources,
		sourceFlags: query.sourceflags ? query.sourceflags : [],

		replacement: '',

		perPage: query.perpage ? query.perpage : 25,
	} );
}

export function getInitialSearch() {
	const sources = getPreload( 'sources', [] );

	return {
		results: [],
		replacements: [],
		replacing: [],

		replaceAll: false,
		replaceCount: 0,
		phraseCount: 0,

		search: getInitialSearchParams( sources ),

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
