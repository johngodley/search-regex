/**
 * Internal dependencies
 */
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ROW,
	SEARCH_DELETE_COMPLETE,
	SEARCH_LOAD_ROW_COMPLETE,
} from '../type';
import SearchRegexApi from 'lib/api-request';
import apiFetch from 'wp-plugin-lib/api-fetch';

export const deleteRow = ( source, rowId ) => ( dispatch ) => {
	apiFetch( SearchRegexApi.source.deleteRow( source, rowId ) )
		.then( json => {
			dispatch( { type: SEARCH_DELETE_COMPLETE, rowId } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};

export const saveRow = ( data, rowId ) => ( dispatch, getState ) => {
	apiFetch( SearchRegexApi.source.saveRow( data.source, rowId, data, getState().search.search ) )
		.then( ( json ) => {
			dispatch( { type: SEARCH_LOAD_ROW_COMPLETE, rowId, row: json.result } );
		} )
		.catch( ( error ) => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};

export const loadRow = ( source, rowId ) => ( dispatch ) => {
	apiFetch( SearchRegexApi.source.loadRow( source, rowId ) )
		.then( json => {
			dispatch( { type: SEARCH_LOAD_ROW_COMPLETE, rowId, row: json.result } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};
