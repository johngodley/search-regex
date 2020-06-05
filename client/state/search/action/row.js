/**
 * Internal dependencies
 */
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ROW,
	SEARCH_DELETE_COMPLETE,
	SEARCH_LOAD_ROW_COMPLETE,
	SEARCH_SAVE_ROW_COMPLETE,
} from '../type';
import { getApi, SearchRegexApi } from 'wp-plugin-library/lib/api';

export const deleteRow = ( source, rowId ) => ( dispatch ) => {
	getApi( SearchRegexApi.source.deleteRow( source, rowId ) )
		.then( json => {
			dispatch( { type: SEARCH_DELETE_COMPLETE, rowId } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};

export const loadRow = ( source, rowId ) => ( dispatch ) => {
	getApi( SearchRegexApi.source.loadRow( source, rowId ) )
		.then( json => {
			dispatch( { type: SEARCH_LOAD_ROW_COMPLETE, rowId, row: json.result } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};

export const saveRow = ( source, rowId, columnId, content ) => ( dispatch, getState ) => {
	const { searchPhrase, searchFlags, replacement, sourceFlags } = getState().search.search;
	const searchValues = { searchPhrase, replacement, searchFlags: Object.keys( searchFlags ), sourceFlags: Object.keys( sourceFlags ) };

	getApi( SearchRegexApi.source.saveRow( source, rowId, { ...searchValues, columnId, content } ) )
		.then( json => {
			dispatch( { type: SEARCH_SAVE_ROW_COMPLETE, ...json, rowId } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};
