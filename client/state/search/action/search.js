/**
 * Internal dependencies
 */
import {
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_START_FRESH,
	SEARCH_START_MORE,
	SEARCH_FORWARD,
	SEARCH_PERFORM_FRESH,
	SEARCH_PERFORM_MORE,
} from '../type';
import { getSearchValues } from '../selector';
import SearchRegexApi from 'lib/api-request';
import apiFetch from 'wp-plugin-lib/api-fetch';

function doSearch( state, dispatch, type, page, extra ) {
	const { search } = state.search;
	const searchValues = getSearchValues( { ...search, page, ...extra } );

	dispatch( { type, ...searchValues } );

	return getSearch( searchValues, dispatch );
}

// Limit is used if we only need a few more results to fill the page
function doSearchMore( state, dispatch, type, page, perPage, extra ) {
	const { search, searchDirection = SEARCH_FORWARD } = state.search;
	const searchValues = getSearchValues( {
		...search,
		page,
		perPage,
		searchDirection,
		...extra,
	} );

	dispatch( { type, ...searchValues } );

	return getSearch( searchValues, dispatch );
}

/**
 * Start a search for the conditions
 * @param {number} page Page offset
 * @param {String} searchDirection Search direction - SEARCH_FORWARD or SEARCH_BACKWARD
 */
export const search = ( page, searchDirection = SEARCH_FORWARD ) => ( dispatch, getState ) => {
	return doSearch( getState(), dispatch, SEARCH_START_FRESH, page, { searchDirection } );
};

/**
 * Continue a search for the conditions
 * @param {number} page Page offset
 * @param {number} perPage Number of results per page
 * @param {number} limit How many results remaining to return
 */
export const searchMore = ( page, perPage, limit ) => ( dispatch, getState ) => {
	doSearchMore( getState(), dispatch, SEARCH_START_MORE, page, perPage, { limit } );
};

/**
 * Perform a search action for the conditions
 * @param {number} page Page offset
 */
export const perform = ( page ) => ( dispatch, getState ) => {
	return doSearch( getState(), dispatch, SEARCH_PERFORM_FRESH, page, { save: true } );
};

/**
 * Continue a search for action the conditions
 * @param {number} page Page offset
 * @param {number} perPage Number of results per page
 */
export const performMore = ( page, perPage ) => ( dispatch, getState ) => {
	doSearchMore( getState(), dispatch, SEARCH_PERFORM_MORE, page, perPage, { save: true } );
};

/**
 * Performs an API search
 * @param {*} searchValues
 * @param {*} dispatch
 */
const getSearch = ( searchValues, dispatch ) =>
	apiFetch( SearchRegexApi.search.perform( searchValues ) )
		.then( ( json ) => {
			dispatch( { type: SEARCH_COMPLETE, ...json, perPage: searchValues.perPage } );
		} )
		.catch( ( error ) => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );
