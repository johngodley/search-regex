import type { SearchState, SearchValues, Result } from '../../types/search';
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
import { isAlreadyFinished, isAdvancedSearch, getSearchFromPreset } from './selector';

interface ExtendedSearchState extends SearchState {
	resultsDirty: boolean;
	replacing: string[];
	labels: Array< { value: string; label: string; labelId?: string } >;
	isSaving: boolean;
}

interface SearchAction {
	type: string;
	searchValue?: Partial< SearchValues >;
	clearAll?: boolean;
	page?: number;
	searchDirection?: string;
	isSave?: boolean;
	progress?: {
		next: boolean | number;
		previous: boolean | number;
	};
	results?: Result[];
	totals?: {
		matched_rows: number;
		rows: number;
		custom?: Array< { name: string; value: number } >;
	};
	rowId?: string;
	row?: Result;
	preset?: { search: SearchValues; id: string } | null;
	labelId?: string;
	labelValue?: string;
	perPage?: number;
}

function shouldRequestMore( state: ExtendedSearchState, action: SearchAction ): boolean {
	if ( state.isSaving && action.progress?.next !== false ) {
		return true;
	}

	const search = state.search as SearchValues;

	if ( search.perPage === -1 && action.progress?.next !== false ) {
		return true;
	}

	if ( isAdvancedSearch( search ) ) {
		if ( state.results.length + ( action.results?.length || 0 ) >= ( search.perPage ?? 0 ) ) {
			return false;
		}

		if ( state.searchDirection === SEARCH_FORWARD && action.progress?.next !== false ) {
			return true;
		}

		if ( state.searchDirection === SEARCH_BACKWARD && action.progress?.previous !== false ) {
			return true;
		}
	}

	return false;
}

const resetTotals = () => ( {
	matched_rows: 0,
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

function getTotals(
	newTotals: SearchAction[ 'totals' ],
	existingTotals: SearchAction[ 'totals' ],
	totalRows: number
): SearchAction[ 'totals' ] {
	const previousCustom = existingTotals?.custom || [];

	return {
		...existingTotals,
		...newTotals,
		matched_rows:
			newTotals?.matched_rows === 0
				? ( existingTotals?.matched_rows || 0 ) + totalRows
				: newTotals?.matched_rows || 0,
		rows: newTotals?.rows || 0,
		custom: newTotals?.custom
			? newTotals.custom.map( ( item, pos ) => ( {
					name: item.name,
					value:
						existingTotals?.custom && existingTotals.custom[ pos ]
							? existingTotals.custom[ pos ].value + item.value
							: item.value,
			  } ) )
			: previousCustom,
	};
}

function canResetResults( state: ExtendedSearchState, searchValue: Partial< SearchValues > ): boolean {
	if ( searchValue.replacement !== undefined && searchValue.replacement === ( state.search as any ).replacement ) {
		return false;
	}

	if ( searchValue.actionOption !== undefined && ( state.search as any ).action === 'modify' ) {
		const { filters } = state.search as any;
		const unmatched = searchValue.actionOption.filter( ( action: any ) => {
			const source = filters.find( ( item: any ) => item.type === action.source );
			if ( source ) {
				return ! source.items.find( ( item: any ) => item.column === action.column );
			}

			return true;
		} );

		return unmatched.length > 0;
	}

	return true;
}

function getPrevNext(
	state: ExtendedSearchState,
	action: SearchAction
): { next?: boolean | number; previous?: boolean | number } {
	if ( ! isAdvancedSearch( state.search as SearchValues ) || ( state.search as any ).action ) {
		return {};
	}

	const firstInSet = state.requestCount === 0;
	const progress = state.progress as any;

	return {
		next: state.searchDirection === SEARCH_FORWARD || firstInSet ? action.progress?.next : progress.next,
		previous:
			state.searchDirection === SEARCH_BACKWARD || firstInSet
				? action.progress?.previous
				: progress.previous,
	};
}

export default function searches(
	state: ExtendedSearchState = {} as ExtendedSearchState,
	action: SearchAction
): ExtendedSearchState {
	switch ( action.type ) {
		case SEARCH_LABEL:
			return {
				...state,
				labels: [
					...state.labels.filter( ( item ) => item.labelId !== action.labelId ),
					...( action.labelValue
						? [ { value: action.labelId || '', label: action.labelValue, labelId: action.labelId } ]
						: [] ),
				],
			};

		case PRESET_SELECT:
			return {
				...state,
				...resetAll(),
				search: {
					...( state.search as object ),
					...getSearchFromPreset( action.preset || null ),
				} as any,
			};

		case SEARCH_VALUES:
			const resetResults = canResetResults( state, action.searchValue || {} ) && state.results.length > 0;

			return {
				...state,
				search: { ...( state.search as object ), ...action.searchValue } as any,
				resultsDirty: resetResults,
			};

		case SEARCH_CANCEL:
			return {
				...state,
				...( action.clearAll ? resetAll() : reset() ),
				status: null,
				isSaving: false,
			};

		case SEARCH_FAIL:
			return { ...state, ...reset(), status: STATUS_FAILED, canCancel: false, isSaving: false };

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

		case SEARCH_COMPLETE:
			if ( isAlreadyFinished( state ) ) {
				return state;
			}

			const shouldContinue = shouldRequestMore( state, action );

			return {
				...state,

				results:
					state.searchDirection === SEARCH_FORWARD
						? [ ...state.results, ...( action.results || [] ) ]
						: [ ...( action.results || [] ), ...state.results ],
				progress: {
					...state.progress,
					...action.progress,
					...getPrevNext( state, action ),
				} as any,
				totals: getTotals( action.totals, state.totals, action.results?.length || 0 ) as any,

				requestCount: state.requestCount + 1,
				canCancel: shouldContinue,
				showLoading: shouldContinue,
				status: shouldContinue ? state.status : STATUS_COMPLETE,
			};

		case SEARCH_LOAD_ROW_COMPLETE:
			return {
				...state,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				results: state.results.map( ( item ) =>
					( item as any ).row_id === action.rowId ? action.row : item
				) as any,
				status: STATUS_COMPLETE,
			};

		case SEARCH_REPLACE_ROW:
			return {
				...state,
				replacing: [ ...state.replacing, action.rowId || '' ],
				status: STATUS_IN_PROGRESS,
			};

		case SEARCH_DELETE_COMPLETE:
			return {
				...state,
				results: state.results.filter( ( row ) => ( row as any ).row_id !== action.rowId ) as any,
				replacing: state.replacing.filter( ( item ) => item !== action.rowId ),
				status: STATUS_COMPLETE,
			};
	}

	return state;
}
