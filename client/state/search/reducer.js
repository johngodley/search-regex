/**
 * Internal dependencies
 */

import {
	SEARCH_VALUES,
	SEARCH_START_FRESH,
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_REPLACE_ALL,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_REPLACE_ALL_COMPLETE,
	SEARCH_DELETE_COMPLETE,
	SEARCH_CANCEL,
	SEARCH_BACKWARD,
	SEARCH_FORWARD,
	SEARCH_LOAD_ROW_COMPLETE,
	SEARCH_SAVE_ROW_COMPLETE,
	SEARCH_START_MORE,
} from './type';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';

function mergeProgress( existing, progress, direction, firstInSet ) {
	return {
		...existing,
		current: progress.current,
		next: direction === SEARCH_FORWARD || firstInSet ? progress.next : existing.next,
		previous: direction === SEARCH_BACKWARD || firstInSet ? progress.previous : existing.previous,
		rows: ( existing.rows ? existing.rows : 0 ) + progress.rows,
	};
}

const isComplete = ( action, results, direction ) => ( direction === SEARCH_FORWARD && action.progress.next === false ) || ( direction === SEARCH_BACKWARD && action.progress.previous === false ) || results.length === action.perPage;
const isAdvancedSearch = ( search ) => search.searchFlags.regex;

function getSimpleState( state, action ) {
	return {
		...state,
		status: STATUS_COMPLETE,
		results: action.results,
		progress: action.progress,
		totals: action.totals ? { ...state.totals, ...action.totals } : state.totals,
		canCancel: false,
		showLoading: false,
	};
}

function getAdvancedState( state, action ) {
	const results = state.searchDirection === SEARCH_FORWARD ? state.results.concat( action.results ) : action.results.concat( state.results );

	return {
		...state,
		status: isComplete( action, results, state.searchDirection ) ? STATUS_COMPLETE : state.status,
		results,
		requestCount: state.requestCount + 1,
		progress: mergeProgress( state.progress, action.progress, state.searchDirection, state.requestCount === 0 ),
		totals: { ...state.totals, ...action.totals },
		canCancel: false,
		showLoading: false,
	};
}

function searchState( state, action ) {
	if ( isAdvancedSearch( state.search ) ) {
		return getAdvancedState( state, action );
	}

	return getSimpleState( state, action );
}

const resetAll = () => ( { ...reset(), results: [], totals: [], progress: {} } );

const reset = () => ( {
	requestCount: 0,
	searchedPhrase: '',
	replaceCount: 0,
	phraseCount: 0,
	status: null,
	replacing: [],
	replaceAll: false,
	row: null,
	canCancel: false,
	showLoading: false,
} );

function hasReplaceFinished( state, action ) {
	const replaceCount = action.results.rows + state.replaceCount;
	const total = action.totals.matches ? action.totals.matches : ( action.totals.rows ? action.totals.rows : state.totals.rows );

	if ( action.progress.next === false || replaceCount >= total ) {
		return true;
	}

	if ( state.totals.matches > 0 && replaceCount >= state.totals.matches ) {
		return true;
	}

	return false;
}

const isAlreadyFinished = ( state ) => state.status === STATUS_COMPLETE || state.status === null;

export default function redirects( state = {}, action ) {
	switch ( action.type ) {
		case SEARCH_VALUES:
			return {
				...state,
				search: { ...state.search, ...action.searchValue },
				results: action.searchValue.replacement !== undefined ? state.results : [],
				status: action.searchValue.replacement !== undefined ? state.status : null,
			};

		case SEARCH_CANCEL:
			if ( action.clearAll ) {
				return { ...state, ...resetAll() };
			}

			return { ...state, ...reset() };

		case SEARCH_START_FRESH:
			return {
				...state,
				requestCount: 0,
				status: STATUS_IN_PROGRESS,
				totals: action.page === 0 ? [] : state.totals,
				progress: action.page === 0 ? [] : state.progress,
				results: [],
				searchDirection: action.searchDirection,
				searchedPhrase: action.replacement,
				showLoading: true,
			};

		case SEARCH_START_MORE:
			return {
				...state,
				canCancel: true,
				showLoading: true,
			};

		case SEARCH_COMPLETE:
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			return searchState( state, action );

		case SEARCH_REPLACE_COMPLETE:
			// If cancelled then don't bother updating
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			// Replace the result with the new details, and remove the replacing status
			return {
				...searchState( { ...state, results: [] }, action ),
				replacing: state.replacing.filter( item => item !== action.rowId ),
			};

		case SEARCH_REPLACE_ALL:
			return { ...state, ...resetAll(), replaceAll: true, status: STATUS_IN_PROGRESS, canCancel: true };

		case SEARCH_REPLACE_ALL_COMPLETE:
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			return {
				...state,
				replaceCount: action.results.rows + state.replaceCount,
				phraseCount: action.results.phrases + state.phraseCount,
				requestCount: action.progress.next === false ? 0 : state.requestCount + 1,
				progress: action.progress,
				totals: { ...state.totals, ...action.totals },
				status: hasReplaceFinished( state, action ) ? STATUS_COMPLETE : STATUS_IN_PROGRESS,
			};

		case SEARCH_SAVE_ROW_COMPLETE:
			return {
				...state,
				status: STATUS_COMPLETE,
				replacing: state.replacing.filter( item => item !== action.rowId ),
				results: state.results.map( ( result ) => ( result.row_id === action.result.row_id ) ? action.result : result ),
				rawData: null,
			};

		case SEARCH_LOAD_ROW_COMPLETE:
			return {
				...state,
				replacing: state.replacing.filter( item => item !== action.rowId ),
				status: STATUS_COMPLETE,
				rawData: action.row,
			};

		case SEARCH_REPLACE_ROW:
			return { ...state, replacing: state.replacing.concat( action.rowId ), status: STATUS_IN_PROGRESS, rawData: null };

		case SEARCH_FAIL:
			return { ...state, ...reset(), status: STATUS_FAILED };

		case SEARCH_DELETE_COMPLETE:
			return {
				...state,
				results: state.results.filter( row => row.row_id !== action.rowId ),
				replacing: state.replacing.filter( item => item !== action.rowId ),
				status: STATUS_COMPLETE,
			};
	}

	return state;
}
