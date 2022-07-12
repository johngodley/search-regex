/**
 * Internal dependencies
 */

import {
	SEARCH_VALUES,
	SEARCH_START_FRESH,
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_REPLACE_ROW,
	SEARCH_PERFORM_FRESH,
	SEARCH_PERFORM_MORE,
	SEARCH_DELETE_COMPLETE,
	SEARCH_CANCEL,
	SEARCH_BACKWARD,
	SEARCH_FORWARD,
	SEARCH_LOAD_ROW_COMPLETE,
	SEARCH_START_MORE,
	SEARCH_LABEL,
} from './type';
import { PRESET_SELECT } from '../preset/type';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from '../settings/type';
import {
	isAlreadyFinished,
	isAdvancedSearch,
	getSearchFromPreset,
} from './selector';

/** @typedef {import('./type.js').SearchState} SearchState */

function shouldRequestMore( state, action ) {
	// We're performing an action - keep going until finished
	if ( state.isSaving && action.progress.next !== false ) {
		return true;
	}

	// No limit
	if ( state.search.perPage === -1 && action.progress.next !== false ) {
		return true;
	}

	// Advanced search - only stop if we have a full page, otherwise keep going
	if ( isAdvancedSearch( state.search ) ) {
		if ( state.results.length + action.results.length >= state.search.perPage ) {
			return false;
		}

		if ( state.searchDirection === SEARCH_FORWARD && action.progress.next !== false ) {
			return true;
		}

		if ( state.searchDirection === SEARCH_BACKWARD && action.progress.previous !== false ) {
			return true;
		}
	}

	// Standard search - stop after one page is returned
	return false;
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
	status: null,
	replacing: [],
	canCancel: false,
	showLoading: false,
} );

function getTotals( newTotals, existingTotals, totalRows ) {
	const previousCustom = existingTotals.custom || [];

	return {
		...existingTotals,
		...newTotals,

		matched_rows: existingTotals.matched_rows + totalRows,
		custom: newTotals.custom
			? newTotals.custom.map( ( item, pos ) => ( {
					name: item.name,
					value:
						existingTotals.custom && existingTotals.custom[ pos ]
							? existingTotals.custom[ pos ].value + item.value
							: item.value,
			  } ) )
			: previousCustom,
	};
}

function canResetResults( state, searchValue ) {
	if ( searchValue.replacement !== undefined && searchValue.replacement === state.replacement ) {
		return false;
	}

	if ( searchValue.actionOption !== undefined && searchValue.action === 'modify' ) {
		const { filters } = state.search;
		const unmatched = searchValue.actionOption.filter( ( action ) => {
			const source = filters.find( ( item ) => item.type === action.source );
			if ( source ) {
				return ! source.items.find( ( item ) => item.column === action.column );
			}

			return true;
		} );

		return unmatched.length > 0;
	}

	return true;
}

function getPrevNext( state, action ) {
	if ( ! isAdvancedSearch( state.search ) || state.search.action ) {
		return {};
	}

	const firstInSet = state.requestCount === 0;

	return {
		next: state.searchDirection === SEARCH_FORWARD || firstInSet ? action.progress.next : state.progress.next,
		previous:
			state.searchDirection === SEARCH_BACKWARD || firstInSet
				? action.progress.previous
				: state.progress.previous,
	};
}

/**
 * Search reducer
 * @param {SearchState} state
 * @param {object} action
 */
export default function searches( state = {}, action ) {
	switch ( action.type ) {
		case SEARCH_LABEL:
			return {
				...state,
				labels: state.labels
					.filter( ( item ) => item.labelId !== action.labelId )
					.concat( action.labelValue ? [ { value: action.labelId, label: action.labelValue } ] : [] ),
			};

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
			const resetResults = canResetResults( state, action.searchValue ) && state.results.length > 0;

			return {
				...state,
				search: { ...state.search, ...action.searchValue },
				resultsDirty: resetResults,
			};

		// Cancel or clear requests
		case SEARCH_CANCEL:
			// if `clearAll` is set then also clear results
			return {
				...state,
				...( action.clearAll ? resetAll() : reset() ),
				status: null,
				isSaving: false,
			};

		case SEARCH_FAIL:
			return { ...state, ...reset(), status: STATUS_FAILED, canCancel: false, isSaving: false };

		// Start a new search
		case SEARCH_PERFORM_FRESH:
		case SEARCH_START_FRESH:
			return {
				...state,
				requestCount: 0,
				resultsDirty: false,
				status: STATUS_IN_PROGRESS,
				totals: action.page === 0 ? resetTotals() : state.totals,
				progress: action.page === 0 ? {} : state.progress,
				results: [],
				searchDirection: action.searchDirection || SEARCH_FORWARD,
				showLoading: true,
				isSaving: action.type === SEARCH_PERFORM_FRESH,
			};

		// Continue search
		case SEARCH_PERFORM_MORE:
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
				// If the user cancelled the action then ignore any changes that come in after
				return state;
			}

			const shouldContinue = shouldRequestMore( state, action );

			return {
				...state,

				// Update results
				results:
					state.searchDirection === SEARCH_FORWARD
						? state.results.concat( action.results )
						: action.results.concat( state.results ),
				progress: {
					...state.progress,
					...action.progress,
					...getPrevNext( state, action ),
					current: ( state.progress.current ? state.progress.current : 0 ) + action.progress.rows,
					rows: ( state.progress.rows ? state.progress.rows : 0 ) + action.progress.rows,
				},
				totals: getTotals( action.totals, state.totals, action.results.length ),

				// Set status
				requestCount: state.requestCount + 1,
				canCancel: shouldContinue,
				showLoading: shouldContinue,
				status: shouldContinue ? state.status : STATUS_COMPLETE,
			};

		case SEARCH_LOAD_ROW_COMPLETE:
			return {
				...state,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				results: state.results.map( ( item ) => ( item.row_id === action.rowId ? action.row : item ) ),
				status: STATUS_COMPLETE,
			};

		case SEARCH_REPLACE_ROW:
			return {
				...state,
				replacing: state.replacing.concat( action.rowId ),
				status: STATUS_IN_PROGRESS,
			};

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
