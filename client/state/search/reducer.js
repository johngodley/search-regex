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
import { PRESET_SELECT } from 'state/preset/type';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';
import { isAlreadyFinished, isComplete, isAdvancedSearch, getSearchFromPreset } from './selector';

function mergeProgress( existing, progress, direction, firstInSet ) {
	return {
		...existing,
		current: progress.current,
		next: direction === SEARCH_FORWARD || firstInSet ? progress.next : existing.next,
		previous: direction === SEARCH_BACKWARD || firstInSet ? progress.previous : existing.previous,
		rows: ( existing.rows ? existing.rows : 0 ) + progress.rows,
	};
}

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
	const results =
		state.searchDirection === SEARCH_FORWARD
			? state.results.concat( action.results )
			: action.results.concat( state.results );
	const status = isComplete( action, results, state.searchDirection ) ? STATUS_COMPLETE : state.status;

	return {
		...state,
		status,
		results,
		requestCount: state.requestCount + 1,
		progress: mergeProgress( state.progress, action.progress, state.searchDirection, state.requestCount === 0 ),
		totals: {
			rows: action.totals.rows,
			matched_rows: state.totals.matched_rows + action.progress.rows,
			matched_phrases:
				( state.totals.matched_phrases || 0 ) +
				action.results.reduce( ( prev, current ) => prev + current.match_count, 0 ),
		},
		canCancel: status !== STATUS_COMPLETE,
		showLoading: status !== STATUS_COMPLETE,
	};
}

function searchState( state, action ) {
	if ( isAdvancedSearch( state.search.searchFlags ) ) {
		return getAdvancedState( state, action );
	}

	return getSimpleState( state, action );
}

const resetTotals = () => ( {
	matched_rows: 0,
	matched_phrases: 0,
	rows: 0,
} );

const resetAll = () => ( {
	...reset(),
	results: [],
	totals: resetTotals(),
	progress: {},
} );

const reset = () => ( {
	requestCount: 0,
	replaceCount: 0,
	phraseCount: 0,
	status: null,
	replacing: [],
	replaceAll: false,
	canCancel: false,
	showLoading: false,
} );

function replaceRows( existing, results, rowId ) {
	const newResults = [];

	if ( results.length === 0 ) {
		return existing.filter( ( item ) => item.row_id !== rowId );
	}

	for ( let index = 0; index < existing.length; index++ ) {
		const newResult = results.find( ( result ) => result.row_id === existing[ index ].row_id );

		if ( newResult ) {
			newResults.push( newResult );
		} else {
			newResults.push( existing[ index ] );
		}
	}

	return newResults;
}

function replaceAllProgress( state, action ) {
	if ( state.progress.current ) {
		return {
			current: state.progress.current + action.progress.rows,
		};
	}

	return {
		current: action.progress.rows,
	};
}

export default function searches( state = {}, action ) {
	switch ( action.type ) {
		// Update the search values when a preset is selected
		case PRESET_SELECT:
			return {
				...state,
				...resetAll(),
				search: {
					...state.search,
					...getSearchFromPreset( action.preset ),
				},
			};

		// Change search values and clear any results, unless the change was the replace value
		case SEARCH_VALUES:
			const resetResults = action.searchValue.replacement !== undefined;

			return {
				...state,
				search: { ...state.search, ...action.searchValue },
				results: resetResults ? state.results : [],
				status: resetResults ? state.status : null,
			};

		// Cancel or clear requests
		case SEARCH_CANCEL:
			// if `clearAll` is set then also clear results
			return {
				...state,
				...( action.clearAll ? resetAll() : reset() ),
				status: STATUS_COMPLETE,
			};

		// Start a new search
		case SEARCH_START_FRESH:
			return {
				...state,
				requestCount: 0,
				status: STATUS_IN_PROGRESS,
				totals: action.page === 0 ? resetTotals() : state.totals,
				progress: action.page === 0 ? {} : state.progress,
				results: [],
				searchDirection: action.searchDirection,
				showLoading: true,
			};

		// Continue search
		case SEARCH_START_MORE:
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			return {
				...state,
				canCancel: true,
				showLoading: true,
			};

		// Search complete
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
				...state,
				results: replaceRows( state.results, action.result, action.rowId ),
				status: STATUS_COMPLETE,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
			};

		case SEARCH_REPLACE_ALL:
			return {
				...state,
				...resetAll(),
				replaceAll: true,
				status: STATUS_IN_PROGRESS,
				canCancel: true,
			};

		case SEARCH_REPLACE_ALL_COMPLETE:
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			return {
				...state,
				replaceCount: action.replaced.rows + state.replaceCount,
				phraseCount: action.replaced.phrases + state.phraseCount,
				requestCount: action.progress.next === false ? 0 : state.requestCount + 1,
				status: action.progress.next === false ? STATUS_COMPLETE : STATUS_IN_PROGRESS,

				// this needs to
				progress: {
					...replaceAllProgress( state, action ),
					next: action.progress.next,
				},

				// this needs to record the original totals, then ignore subsequent ones
				totals: state.totals.rows === 0 ? action.totals : state.totals,

				canCancel: action.progress.next !== false,
			};

		case SEARCH_SAVE_ROW_COMPLETE:
			return {
				...state,
				status: STATUS_COMPLETE,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				results: state.results.map( ( result ) =>
					result.row_id === action.result.row_id ? action.result : result
				),
				rawData: null,
			};

		case SEARCH_LOAD_ROW_COMPLETE:
			return {
				...state,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				status: STATUS_COMPLETE,
				rawData: action.row,
			};

		case SEARCH_REPLACE_ROW:
			return {
				...state,
				replacing: state.replacing.concat( action.rowId ),
				status: STATUS_IN_PROGRESS,
				rawData: null,
			};

		case SEARCH_FAIL:
			return { ...state, ...reset(), status: STATUS_FAILED };

		case SEARCH_DELETE_COMPLETE:
			return {
				...state,
				results: state.results.filter( ( row ) => row.row_id !== action.rowId ),
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				status: STATUS_COMPLETE,
			};
	}

	return state;
}
