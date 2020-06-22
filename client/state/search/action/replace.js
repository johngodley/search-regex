/**
 * Internal dependencies
 */
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ALL,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_REPLACE_ALL_COMPLETE,
	SEARCH_REPLACE_ALL_MORE,
} from '../type';
import { getSearchValues, getReplacement } from '../selector';
import SearchRegexApi from 'lib/api-request';
import apiFetch from 'wp-plugin-lib/api-fetch';

/**
 * Replace within a single row. Can replace an individual phrase, everything in a column, or everything in a row
 *
 * @param {String} replacement String to replace the phrase with
 * @param {String} source The source name
 * @param {number} rowId Row ID
 * @param {String|null} columnId Optional column to replace
 * @param {number|null} posId Optional position in column to replace
 */
export const replaceRow = ( replacement, source, rowId, columnId = null, posId = null ) => ( dispatch, getState ) => {
	const { search, sources, tagged } = getState().search;
	const replace = {
		...getSearchValues( search, tagged, sources ),
		replacePhrase: replacement,
	};

	if ( columnId ) {
		replace.columnId = columnId;
	}

	if ( posId ) {
		replace.posId = posId;
	}

	delete replace.source;

	dispatch( { type: SEARCH_REPLACE_ROW, rowId } );

	return apiFetch( SearchRegexApi.source.replaceRow( source, rowId, replace ) )
		.then( ( json ) => {
			dispatch( { type: SEARCH_REPLACE_COMPLETE, ...json, perPage: search.perPage, rowId } );
		} )
		.catch( ( error ) => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );
};

/**
 * Action to replace all phrases with the replacement
 * @param {number} perPage Number of results per page
 * @returns {Promise} API fetch promise
 */
export const replaceAll = ( perPage ) => ( dispatch, getState ) => {
	const { search, sources, tagged } = getState().search;
	const searchValues = getSearchValues( search, tagged, sources );
	const replace = {
		...searchValues,
		replacePhrase: getReplacement( searchValues.replacement ),
		offset: '0',
		perPage,
	};

	dispatch( { type: SEARCH_REPLACE_ALL } );

	return replaceAllRequest( replace, dispatch );
};

/**
 * Action to continue replacing all
 * @param {number} page
 * @returns {Promise} API fetch promise
 */
export const replaceNext = ( offset, perPage ) => ( dispatch, getState ) => {
	const { search, sources, tagged } = getState().search;
	const searchValues = getSearchValues( search, tagged, sources );

	const replace = {
		...searchValues,
		replacePhrase: getReplacement( searchValues.replacement ),
		offset,
		perPage,
	};

	dispatch( { type: SEARCH_REPLACE_ALL_MORE } );

	return replaceAllRequest( replace, dispatch );
};

const replaceAllRequest = ( values, dispatch ) =>
	apiFetch( SearchRegexApi.search.replace( values ) )
		.then( ( json ) => {
			dispatch( { type: SEARCH_REPLACE_ALL_COMPLETE, ...json } );
		} )
		.catch( ( error ) => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );
