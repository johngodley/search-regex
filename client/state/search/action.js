/**
 * Internal dependencies
 */
import {
	SEARCH_START_FRESH,
	SEARCH_START_MORE,
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_REPLACE_ALL,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_REPLACE_ALL_COMPLETE,
	SEARCH_REPLACE_ALL_MORE,
	SEARCH_DELETE_COMPLETE,
	SEARCH_LOAD_ROW_COMPLETE,
	SEARCH_SAVE_ROW_COMPLETE,
	SEARCH_CANCEL,
	SEARCH_VALUES,
	SEARCH_FORWARD,
} from './type';

import { getApi, SearchRegexApi } from 'lib/api';
import { removePostTypes } from 'lib/sources';

function getSearchValues( values, sources ) {
	return {
		...values,
		source: removePostTypes( values.source, sources ),
		searchFlags: Object.keys( values.searchFlags ),
		sourceFlags: Object.keys( values.sourceFlags ),
	};
}

export const setSearch = ( searchValue ) => ( { type: SEARCH_VALUES, searchValue } );
export const setError = ( error ) => ( { type: SEARCH_FAIL, error: { message: error } } );
export const cancel = () => ( { type: SEARCH_CANCEL, clearAll: false } );
export const clear = () => ( { type: SEARCH_CANCEL, clearAll: true } );

export const search = ( search, page, searchDirection = SEARCH_FORWARD ) => ( dispatch, getState ) => {
	const searchValues = { ...getSearchValues( search, getState().search.sources ), page, searchDirection };

	getApi( SearchRegexApi.search.get( searchValues ) )
		.then( json => {
			dispatch( { type: SEARCH_COMPLETE, ...json, perPage: searchValues.perPage } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_START_FRESH, ...searchValues } );
};

export const searchMore = ( search, page, limit ) => ( dispatch, getState ) => {
	const searchValues = {
		...getSearchValues( search, getState().search.sources ),
		page,
		searchDirection: getState().search.searchDirection,
		limit,
	};

	getApi( SearchRegexApi.search.get( searchValues ) )
		.then( json => {
			dispatch( { type: SEARCH_COMPLETE, ...json, perPage: searchValues.perPage } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_START_MORE, ...searchValues } );
}

export const replaceRow = ( replacement, source, rowId, columnId = null, posId = null ) => ( dispatch, getState ) => {
	const { search } = getState().search;
	const replace = {
		...getSearchValues( search, getState().search.sources ),
		replacePhrase: replacement,
	};

	if ( columnId ) {
		replace.columnId = columnId;
	}

	if ( posId ) {
		replace.posId = posId;
	}

	delete replace.source;

	getApi( SearchRegexApi.source.replaceRow( source, rowId, replace ) )
		.then( json => {
			dispatch( { type: SEARCH_REPLACE_COMPLETE, ...json, perPage: search.perPage, rowId } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ROW, rowId } );
};

export const replaceAll = ( search, page, perPage ) => ( dispatch, getState ) => {
	const replace = {
		...getSearchValues( search, getState().search.sources ),
		replacePhrase: search.replacement,
		page,
		perPage,
	};

	getApi( SearchRegexApi.search.replace( replace ) )
		.then( json => {
			dispatch( { type: SEARCH_REPLACE_ALL_COMPLETE, ...json } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } )
		} );

	return dispatch( { type: SEARCH_REPLACE_ALL } );
};

export const replaceNext = ( page ) => ( dispatch, getState ) => {
	const { search } = getState().search;
	const searchValues = {
		...getSearchValues( getState().search.search, getState().search.sources ),
		replacePhrase: search.replacement,
		page,
		perPage: getState().search.perPage,
	};

	getApi( SearchRegexApi.search.replace( searchValues ) )
		.then( json => {
			dispatch( { type: SEARCH_REPLACE_ALL_COMPLETE, ...json } );
		} )
		.catch( error => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );

	return dispatch( { type: SEARCH_REPLACE_ALL_MORE } );
};

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
