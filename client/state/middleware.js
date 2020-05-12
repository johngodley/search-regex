/**
 * Internal dependencies
 */
import { SEARCH_START_FRESH } from 'state/search/type';
import { setPageUrl } from 'lib/wordpress-url';
import { removePostTypes } from 'lib/sources';

function setUrlForPage( action, state ) {
	const { searchPhrase, searchFlags, source, sourceFlags, perPage } = action;
	const defaults = {
		searchPhrase: '',
		searchFlags: [],
		source: [],
		sourceFlags: [],
		perPage: 25,
		sub: 'search',
	};

	// Remove custom page types if 'posts' is included
	const filteredSource = removePostTypes( source, state.sources );

	setPageUrl( { searchPhrase, searchFlags, source: filteredSource, sourceFlags, perPage }, defaults );
}

export const urlMiddleware = ( store ) => next => action => {
	switch ( action.type ) {
		case SEARCH_START_FRESH:
			setUrlForPage( action, store.getState().search );
			break;
	}

	return next( action );
};
