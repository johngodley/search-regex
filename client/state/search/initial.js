/**
 * Internal dependencies
 */

import { getPageUrl } from 'lib/wordpress-url';
import validateSearch from 'state/search/validate';

function getInitialSearchParams() {
	const query = getPageUrl();

	return validateSearch( {
		searchPhrase: query.searchphrase ? query.searchphrase : '',
		searchFlags: query.searchflags ? query.searchflags : [ 'case' ],

		source: query.source ? query.source : [ 'posts' ],
		sourceFlags: query.sourceflags ? query.sourceflags : [],

		replacement: '',

		perPage: query.perpage ? query.perpage : 25,
	} );
}

export function getInitialSearch() {
	return {
		results: [],
		replacements: [],
		replacing: [],

		replaceAll: false,
		replaceCount: 0,
		phraseCount: 0,

		search: getInitialSearchParams(),

		searchDirection: null,
		searchedPhrase: '',  // needed?

		requestCount: 0,
		totals: {},
		progress: {},

		status: null,
		showLoading: false,

		sources: SearchRegexi10n.preload && SearchRegexi10n.preload.sources ? SearchRegexi10n.preload.sources : [],
		sourceFlags: SearchRegexi10n.preload && SearchRegexi10n.preload.source_flags ? SearchRegexi10n.preload.source_flags : [],

		rawData: null,

		canCancel: false,
	};
}
