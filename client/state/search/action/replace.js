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
import { getPreset, getDefaultPresetValues } from 'state/preset/selector';
import apiFetch from 'wp-plugin-lib/api-fetch';

function getReplaceValue( replacement, { presets, currentPreset } ) {
	const preset = getPreset( presets, currentPreset );

	// If no replacement then default to the preset replace value, but with all the tags removed
	if ( preset && replacement === '' ) {
		const defaults = getDefaultPresetValues( preset );

		return getReplacement( defaults.replacement );
	}

	return getReplacement( replacement );
}

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
	const { search, sources } = getState().search;
	const replaceValue = getReplaceValue( replacement, getState().preset )
	const replace = {
		...getSearchValues( search, sources ),
		replacePhrase: getReplacement( replaceValue ),
		replacement: replaceValue,
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
	const { search, sources } = getState().search;
	const searchValues = getSearchValues( search, sources );
	const replaceValue = getReplaceValue( search.replacement, getState().preset )
	const replace = {
		...searchValues,
		replacePhrase: getReplacement( replaceValue ),
		replacement: replaceValue,
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
	const { search, sources } = getState().search;
	const searchValues = getSearchValues( search, sources );
	const replaceValue = getReplaceValue( search.replacement, getState().preset )
	const replace = {
		...searchValues,
		replacement: replaceValue,
		replacePhrase: getReplacement( replaceValue ),
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
