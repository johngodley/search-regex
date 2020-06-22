/**
 * Internal dependencies
 */
import {
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_START_FRESH,
	SEARCH_START_MORE,
	SEARCH_FORWARD,
} from '../type';
import { getSearchValues } from '../selector';
import SearchRegexApi from 'lib/api-request';
import apiFetch from 'wp-plugin-lib/api-fetch';

/**
 * Start a search for the current phrase and conditions
 * @param {number} page Page offset
 * @param {String} searchDirection Search direction - SEARCH_FORWARD or SEARCH_BACKWARD
 */
export const search = ( page, searchDirection = SEARCH_FORWARD ) => ( dispatch, getState ) => {
	const { sources, search, tagged } = getState().search;
	const searchValues = {
		...getSearchValues( search, tagged, sources ),
		page,
		searchDirection,
	};

	dispatch( { type: SEARCH_START_FRESH, ...searchValues } );

	return getSearch( searchValues, dispatch );
};

/**
 * Continue a search for the current phrase and conditions. Only need for regular expression searches when the page isn't full
 * @param {number} page Page offset
 * @param {number} perPage Number of results per page
 * @param {number} limit How many results remaining to return
 */
export const searchMore = ( page, perPage, limit ) => ( dispatch, getState ) => {
	const { search, sources, tagged, searchDirection = SEARCH_FORWARD } = getState().search;
	const searchValues = {
		...getSearchValues( search, tagged, sources ),
		page,
		perPage,
		searchDirection,
		limit,
	};

	dispatch( { type: SEARCH_START_MORE, ...searchValues } );

	return getSearch( searchValues, dispatch );
}

/**
 * Performs an API search
 * @param {*} searchValues
 * @param {*} dispatch
 */
const getSearch = ( searchValues, dispatch ) =>
	apiFetch( SearchRegexApi.search.get( searchValues ) )
		.then( json => {
			dispatch( { type: SEARCH_COMPLETE, ...json, perPage: searchValues.perPage } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );
