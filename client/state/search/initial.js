/**
 * Internal dependencies
 */

import { getPageUrl } from 'lib/wordpress-url';
import validateSearch from 'state/search/validate';
import { getAllPostTypes } from 'lib/sources';

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

	return validateSearch( {
		searchPhrase: query.searchphrase ? query.searchphrase : '',
		searchFlags: query.searchflags ? query.searchflags : [ 'case' ],

		source: initialSources,
		sourceFlags: query.sourceflags ? query.sourceflags : [],

		replacement: '',

		perPage: query.perpage ? query.perpage : 25,
	} );
}

export function getInitialSearch() {
	const sources = SearchRegexi10n.preload && SearchRegexi10n.preload.sources ? SearchRegexi10n.preload.sources : [];

	return {
		results: [],
		replacements: [],
		replacing: [],

		replaceAll: false,
		replaceCount: 0,
		phraseCount: 0,

		search: getInitialSearchParams( sources ),

		searchDirection: null,
		searchedPhrase: '',  // needed?

		requestCount: 0,
		totals: {},
		progress: {},

		status: null,
		showLoading: false,

		sources,
		sourceFlags: SearchRegexi10n.preload && SearchRegexi10n.preload.source_flags ? SearchRegexi10n.preload.source_flags : [],

		rawData: null,

		canCancel: false,
	};
}
