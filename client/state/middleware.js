/**
 * Internal dependencies
 */
import { SEARCH_START_FRESH } from 'state/search/type';
import { setPageUrl } from 'lib/wordpress-url';

function setUrlForPage( action ) {
	const { searchPhrase, searchFlags, source, sourceFlags, perPage } = action;
	const defaults = {
		searchPhrase: '',
		searchFlags: [],
		source: [],
		sourceFlags: [],
		perPage: 25,
		sub: 'search',
	};

	setPageUrl( { searchPhrase, searchFlags, source, sourceFlags, perPage }, defaults );
}

export const urlMiddleware = () => next => action => {
	switch ( action.type ) {
		case SEARCH_START_FRESH:
			setUrlForPage( action );
			break;
	}

	return next( action );
};
