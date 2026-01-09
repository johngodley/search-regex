import { create } from 'zustand';
import { setPageUrl, getPageUrl } from '@wp-plugin-lib';
import type { SearchValues, SearchSourceGroup, Schema, Result } from '../types/search';
import type { PresetValue } from '../types/preset';
import getPreload from '../lib/preload';
import getValidatedSearch, { getQuerySearchParams, getDefaultSearch, getSearchFromPreset } from '../lib/search-utils';
import type { SearchResponse, SettingsValues } from '../lib/api-schemas';

interface SearchTotals {
	matched_rows: number;
	rows: number;
	custom?: Array< { name: string; value: number } >;
}

interface SearchProgress {
	current?: number;
	rows?: number;
	next: boolean | number;
	previous?: boolean | number | undefined;
}

// Helper functions to convert API response to store types

/**
 * Convert API search response results (with number row_id) to Result[] (with string row_id)
 * @param apiResults
 */
export function convertToResults( apiResults: SearchResponse[ 'results' ] ): Result[] {
	return apiResults.map( ( result ) => ( {
		...result,
		row_id: String( result.row_id ),
	} ) ) as Result[];
}

export function convertToSearchTotals( data: {
	matched_rows: number;
	rows: number;
	custom?: Array< { name: string; value: number } > | undefined;
} ): SearchTotals {
	const result: SearchTotals = {
		matched_rows: data.matched_rows,
		rows: data.rows,
	};
	if ( data.custom !== undefined ) {
		result.custom = data.custom;
	}
	return result;
}

export function convertToSearchProgress( data: {
	next: number | boolean;
	current?: number | undefined;
	rows?: number | undefined;
	previous?: boolean | number | undefined;
} ): SearchProgress {
	// Preserve all fields including current (even if it's 0)
	// Check if current exists in the data object (even if it's 0)
	const hasCurrent = 'current' in data && data.current !== undefined;
	const hasRows = 'rows' in data && data.rows !== undefined;
	const hasPrevious = 'previous' in data && data.previous !== undefined;

	const result: SearchProgress = {
		next: data.next,
	};

	// Explicitly assign each field if it exists
	if ( hasCurrent ) {
		result.current = data.current as number;
	}
	if ( hasRows ) {
		result.rows = data.rows as number;
	}
	if ( hasPrevious && data.previous !== undefined ) {
		result.previous = data.previous;
	}

	return result;
}

function getInitialSearch( presets: PresetValue[], sources?: SearchSourceGroup[], schema?: Schema[] ): SearchValues {
	const query = getPageUrl();
	const defaultSearch = getDefaultSearch();

	const settings = SearchRegexi10n.settings as SettingsValues | undefined;
	let startupMode: SettingsValues[ 'startupMode' ] | undefined = settings?.startupMode;
	let startupPresetId: string | undefined = settings?.startupPreset;

	// Backwards compatibility: fall back to legacy defaultPreset if
	// startupMode has not been initialised yet.
	if ( ! startupMode && settings && ( settings as any ).defaultPreset !== undefined ) {
		const legacyDefault = ( settings as any ).defaultPreset;
		if ( legacyDefault ) {
			startupMode = 'preset';
			startupPresetId = String( legacyDefault );
		}
	}

	const presetSearch = getSearchFromPreset(
		presets.find(
			( item ) =>
				item.id === query.preset ||
				( startupMode === 'preset' && startupPresetId && item.id === startupPresetId )
		) as any
	);
	const querySearch = getQuerySearchParams();

	const merged = {
		...defaultSearch,
		...presetSearch,
		...querySearch, // Query params should override presets and defaults
	};

	return getValidatedSearch( merged as any, sources, schema );
}

function getInitialMode(): 'simple' | 'advanced' {
	const settings = SearchRegexi10n.settings as SettingsValues | undefined;
	const startupMode = settings?.startupMode;
	const queryParams = getQuerySearchParams();

	// Query param overrides startup mode
	if ( queryParams.mode ) {
		return queryParams.mode as 'simple' | 'advanced';
	}

	// Fall back to startup mode or default to advanced
	return startupMode === 'simple' ? 'simple' : 'advanced';
}

interface SearchStore {
	// Search form values
	search: SearchValues;
	setSearch: ( search: Partial< SearchValues > ) => void;

	// UI mode (Simple vs Advanced)
	mode: 'simple' | 'advanced';
	setMode: ( mode: 'simple' | 'advanced' ) => void;

	// Search results
	results: Result[];
	setResults: ( results: Result[] ) => void;

	// Operation state
	status: string | null;
	setStatus: ( status: string | null ) => void;
	isSaving: boolean;
	setIsSaving: ( isSaving: boolean ) => void;
	canCancel: boolean;
	setCanCancel: ( canCancel: boolean ) => void;

	// Computed state - use this instead of subscribing to status directly
	isBusy: boolean;

	// Progress tracking
	totals: SearchTotals;
	setTotals: ( totals: SearchTotals ) => void;
	progress: SearchProgress;
	setProgress: ( progress: SearchProgress ) => void;

	// Cumulative matched rows for advanced search across page navigations
	cumulativeMatchedRows: number;
	setCumulativeMatchedRows: ( count: number ) => void;
	addToCumulativeMatchedRows: ( count: number ) => void;

	// Replace operation
	replaceAll: boolean;
	setReplaceAll: ( replaceAll: boolean ) => void;
	replacing: unknown[];
	setReplacing: ( replacing: unknown[] ) => void;

	// Search direction
	searchDirection: string | null;
	setSearchDirection: ( direction: string | null ) => void;

	// UI state
	showLoading: boolean;
	setShowLoading: ( showLoading: boolean ) => void;
	resultsDirty: boolean;
	setResultsDirty: ( dirty: boolean ) => void;

	// Static data
	sources: SearchSourceGroup[];
	schema: Schema[];
	labels: unknown[];
	setLabels: ( labels: unknown[] ) => void;

	// Actions
	clearResults: () => void;
	updateSearchUrl: () => void;
	initialize: ( presets?: PresetValue[] ) => void;
}

export const useSearchStore = create< SearchStore >()( ( set, get ) => {
	const sources = getPreload( 'sources', [] ) as unknown as SearchSourceGroup[];
	const schema = getPreload< Schema[] >( 'schema', [] );

	return {
		// Initial state - use query params if available, otherwise defaults
		// Note: This will be updated by initialize() when presets are loaded,
		// but we want query params to work even before presets load
		search: getInitialSearch( [], sources, schema ) as SearchValues,
		// Current UI mode. Query params override startup mode.
		mode: getInitialMode(),
		results: [],
		status: null,
		isSaving: false,
		canCancel: false,
		// Computed: true when search is in progress or replacing
		isBusy: false,
		totals: {
			matched_rows: 0,
			rows: 0,
		},
		progress: { next: false },
		replaceAll: false,
		replacing: [],
		searchDirection: null,
		showLoading: false,
		resultsDirty: false,
		cumulativeMatchedRows: 0,
		sources,
		schema,
		labels: getPreload( 'labels', [] ),

		// Setters
		setSearch: ( searchValue ) =>
			set( ( state ) => {
				// If we have results and search params are changing, mark results as dirty
				// A search has been performed if we have a status (even if it's STATUS_COMPLETE)
				const hasPerformedSearch = state.status !== null;
				const shouldMarkDirty = hasPerformedSearch && Object.keys( searchValue ).length > 0;

				return {
					search: { ...state.search, ...searchValue },
					resultsDirty: shouldMarkDirty ? true : state.resultsDirty,
				};
			} ),

		setResults: ( results ) => set( { results } ),
		setStatus: ( status ) =>
			set( ( state ) => ( {
				status,
				isBusy: status === 'in-progress' || state.replaceAll,
			} ) ),
		setIsSaving: ( isSaving ) => set( { isSaving } ),
		setMode: ( mode ) => set( { mode } ),
		setCanCancel: ( canCancel ) => set( { canCancel } ),
		setTotals: ( totals ) => set( { totals } ),
		setProgress: ( progress ) => set( { progress } ),
		setCumulativeMatchedRows: ( cumulativeMatchedRows ) => set( { cumulativeMatchedRows } ),
		addToCumulativeMatchedRows: ( count ) =>
			set( ( state ) => ( { cumulativeMatchedRows: state.cumulativeMatchedRows + count } ) ),
		setReplaceAll: ( replaceAll ) =>
			set( ( state ) => ( {
				replaceAll,
				isBusy: state.status === 'in-progress' || replaceAll,
			} ) ),
		setReplacing: ( replacing ) => set( { replacing } ),
		setSearchDirection: ( searchDirection ) => set( { searchDirection } ),
		setShowLoading: ( showLoading ) => set( { showLoading } ),
		setResultsDirty: ( resultsDirty ) => set( { resultsDirty } ),
		setLabels: ( labels ) => set( { labels } ),

		// Actions
		clearResults: () =>
			set( {
				results: [],
				status: null,
				totals: { matched_rows: 0, rows: 0 },
				progress: { next: false },
				replacing: [],
				resultsDirty: false,
				cumulativeMatchedRows: 0,
			} ),

		updateSearchUrl: () => {
			const query = getPageUrl();

			// If there's a preset selected, just update the preset parameter
			if ( query.preset ) {
				setPageUrl( { page: 'search-regex.php', preset: query.preset }, {} );
				return;
			}

			// Otherwise, update all search parameters
			const { search, mode } = get();
			const defaultSearch = getDefaultSearch();
			const settings = SearchRegexi10n.settings as SettingsValues | undefined;
			const startupMode = settings?.startupMode === 'simple' ? 'simple' : 'advanced';

			const defaults = {
				searchPhrase: defaultSearch.searchPhrase,
				searchFlags: defaultSearch.searchFlags,
				source: defaultSearch.source,
				perPage: defaultSearch.perPage,
				sub: 'search',
				filters: defaultSearch.filters,
				view: defaultSearch.view,
				mode: startupMode, // Only include mode in URL if different from startup mode
			};

			setPageUrl(
				{
					page: 'search-regex.php',
					searchPhrase: search.searchPhrase,
					searchFlags: search.searchFlags,
					source: search.source,
					perPage: search.perPage,
					filters: search.filters && search.filters.length > 0 ? search.filters : null,
					view: search.view ? ( search.view as string[] ) : [],
					mode, // Include current mode
				},
				defaults
			);
		},

		initialize: ( presets = [] ) => {
			const state = get();
			const search = getInitialSearch( presets, state.sources, state.schema );
			set( { search } );
		},
	};
} );
