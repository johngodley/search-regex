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

function getInitialSearch( presets: PresetValue[] ): SearchValues {
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

	return getValidatedSearch( merged as any );
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

	// Progress tracking
	totals: SearchTotals;
	setTotals: ( totals: SearchTotals ) => void;
	progress: SearchProgress;
	setProgress: ( progress: SearchProgress ) => void;

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

export const useSearchStore = create< SearchStore >()( ( set, get ) => ( {
	// Initial state - use query params if available, otherwise defaults
	// Note: This will be updated by initialize() when presets are loaded,
	// but we want query params to work even before presets load
	search: getInitialSearch( [] ) as SearchValues,
	// Current UI mode. If startupMode is "simple" we start in simple mode,
	// otherwise we default to advanced.
	mode: ( SearchRegexi10n.settings as SettingsValues | undefined )?.startupMode === 'simple' ? 'simple' : 'advanced',
	results: [],
	status: null,
	isSaving: false,
	canCancel: false,
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
	sources: getPreload( 'sources', [] ) as unknown as SearchSourceGroup[],
	schema: getPreload< Schema[] >( 'schema', [] ),
	labels: getPreload( 'labels', [] ),

	// Setters
	setSearch: ( searchValue ) =>
		set( ( state ) => ( {
			search: { ...state.search, ...searchValue },
		} ) ),

	setResults: ( results ) => set( { results } ),
	setStatus: ( status ) => set( { status } ),
	setIsSaving: ( isSaving ) => set( { isSaving } ),
	setMode: ( mode ) => set( { mode } ),
	setCanCancel: ( canCancel ) => set( { canCancel } ),
	setTotals: ( totals ) => set( { totals } ),
	setProgress: ( progress ) => set( { progress } ),
	setReplaceAll: ( replaceAll ) => set( { replaceAll } ),
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
		} ),

	updateSearchUrl: () => {
		const query = getPageUrl();

		// If there's a preset selected, just update the preset parameter
		if ( query.preset ) {
			setPageUrl( { page: 'search-regex.php', preset: query.preset }, {} );
			return;
		}

		// Otherwise, update all search parameters
		const defaults = {
			searchPhrase: '',
			searchFlags: [ 'case' ],
			source: [ 'posts' ],
			perPage: 25,
			sub: 'search',
			filters: [
				{
					type: 'posts',
					items: [
						{
							column: 'post_type',
							logic: 'include',
							values: [ 'post', 'page' ],
						},
					],
				},
			],
			view: [],
		};

		const { search } = get();
		const filters = search.filters && search.filters.length > 0 ? search.filters : null;

		setPageUrl(
			{
				page: 'search-regex.php',
				searchPhrase: search.searchPhrase,
				searchFlags: search.searchFlags,
				source: search.source,
				perPage: search.perPage,
				filters,
				view: search.view ? ( search.view as string[] ) : [],
			},
			defaults
		);
	},

	initialize: ( presets = [] ) => {
		const search = getInitialSearch( presets );
		set( { search } );
	},
} ) );
