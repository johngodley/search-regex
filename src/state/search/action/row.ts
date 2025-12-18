import type { ThunkAction } from 'redux-thunk';
import { SEARCH_FAIL, SEARCH_REPLACE_ROW, SEARCH_DELETE_COMPLETE, SEARCH_LOAD_ROW_COMPLETE } from '../type';
import SearchRegexApi from '../../../lib/api-request';
import { apiFetch } from '@wp-plugin-lib';

interface RootState {
	search: {
		search: unknown;
	};
}

export const deleteRow =
	( source: string, rowId: string ): ThunkAction< { type: string; rowId: string }, RootState, unknown, any > =>
	( dispatch ) => {
		apiFetch( SearchRegexApi.source.deleteRow( source, rowId ) )
			.then( () => {
				dispatch( { type: SEARCH_DELETE_COMPLETE, rowId } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: SEARCH_FAIL, error } );
			} );

		return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
	};

export const saveRow =
	(
		data: { source: string; [ key: string ]: any },
		rowId: string
	): ThunkAction< { type: string; rowId: string }, RootState, unknown, any > =>
	( dispatch, getState ) => {
		apiFetch( SearchRegexApi.source.saveRow( data.source, rowId, data, getState().search.search ) )
			.then( ( response: any ) => {
				dispatch( { type: SEARCH_LOAD_ROW_COMPLETE, rowId, row: response.result } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: SEARCH_FAIL, error } );
			} );

		return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
	};

export const loadRow =
	( source: string, rowId: string ): ThunkAction< { type: string; rowId: string }, RootState, unknown, any > =>
	( dispatch ) => {
		apiFetch( SearchRegexApi.source.loadRow( source, rowId ) )
			.then( ( json: any ) => {
				dispatch( { type: SEARCH_LOAD_ROW_COMPLETE, rowId, row: json.result } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: SEARCH_FAIL, error } );
			} );

		return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
	};
