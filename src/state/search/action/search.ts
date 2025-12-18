import type { ThunkAction } from 'redux-thunk';
import type { Dispatch } from 'redux';
import {
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_START_FRESH,
	SEARCH_START_MORE,
	SEARCH_FORWARD,
	SEARCH_PERFORM_FRESH,
	SEARCH_PERFORM_MORE,
} from '../type';
import type { SearchValues } from '../../../types/search';
import { getSearchValues } from '../selector';
import SearchRegexApi from '../../../lib/api-request';
import { apiFetch } from '@wp-plugin-lib';

interface RootState {
	search: {
		search: unknown;
		searchDirection?: string;
	};
}

function doSearch(
	state: RootState,
	dispatch: Dispatch,
	type: string,
	page: number,
	extra: Record< string, unknown >
): Promise< void > {
	const { search } = state.search;
	const searchValues = getSearchValues( { ...( search as object ), page, ...extra } as SearchValues );

	dispatch( { type, ...searchValues } );

	return getSearch( searchValues, dispatch );
}

function doSearchMore(
	state: RootState,
	dispatch: Dispatch,
	type: string,
	page: number,
	perPage: number,
	extra: Record< string, unknown >
): Promise< void > {
	const { search, searchDirection = SEARCH_FORWARD } = state.search;
	const searchValues = getSearchValues( {
		...( search as object ),
		page,
		perPage,
		searchDirection,
		...extra,
	} as SearchValues );

	dispatch( { type, ...searchValues } );

	return getSearch( searchValues, dispatch );
}

export const search =
	(
		page: number,
		searchDirection: string = SEARCH_FORWARD
	): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch, getState ) => {
		return doSearch( getState(), dispatch, SEARCH_START_FRESH, page, { searchDirection } );
	};

export const searchMore =
	( page: number, perPage: number, limit: number ): ThunkAction< void, RootState, unknown, any > =>
	( dispatch, getState ) => {
		doSearchMore( getState(), dispatch, SEARCH_START_MORE, page, perPage, { limit } );
	};

export const perform =
	( page: number ): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch, getState ) => {
		return doSearch( getState(), dispatch, SEARCH_PERFORM_FRESH, page, { save: true } );
	};

export const performMore =
	( page: number, perPage: number ): ThunkAction< void, RootState, unknown, any > =>
	( dispatch, getState ) => {
		doSearchMore( getState(), dispatch, SEARCH_PERFORM_MORE, page, perPage, { save: true } );
	};

const getSearch = ( searchValues: SearchValues & { save?: boolean }, dispatch: Dispatch ): Promise< void > =>
	apiFetch( SearchRegexApi.search.perform( searchValues ) )
		.then( ( json: any ) => {
			dispatch( { type: SEARCH_COMPLETE, ...json, perPage: searchValues.perPage, isSave: !! searchValues.save } );
		} )
		.catch( ( error: any ) => {
			dispatch( { type: SEARCH_FAIL, error } );
		} );
