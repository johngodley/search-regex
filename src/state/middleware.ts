import type { Middleware } from 'redux';
// @ts-ignore - file-saver doesn't have types
import { saveAs } from 'file-saver';
import { SEARCH_START_FRESH, SEARCH_COMPLETE } from '../state/search/type';
import type { SearchState, Filter } from '../types/search';
import type { PresetValue } from '../types/preset';
import { setPageUrl, removeFromPageUrl, getPageUrl } from '@wp-plugin-lib';
import { PRESET_SELECT } from './preset/type';

interface SearchAction {
	type: typeof SEARCH_START_FRESH;
	searchFlags: string[];
	source: string[];
	perPage: number;
	searchPhrase: string;
	filters: Filter[];
	view: string[];
}

interface SearchCompleteAction {
	type: typeof SEARCH_COMPLETE;
	isSave: boolean;
	progress: {
		next: boolean | number;
	};
	results: unknown[];
}

interface PresetSelectAction {
	type: typeof PRESET_SELECT;
	currentOnly?: boolean;
	preset?: PresetValue;
}

interface PresetState {
	presets: PresetValue[];
	currentPreset: string;
}

interface RootState {
	search: SearchState & { search: { action?: string; actionOption?: { format?: string } }; results: unknown[] };
	preset: PresetState;
}

function filterToQuery( filters: Filter[] ): string | null {
	if ( filters.length > 0 ) {
		return JSON.stringify( filters, { arrayFormat: 'brackets', indices: false } as any );
	}

	return null;
}

function setUrlForPage( action: SearchAction, _searchState: RootState[ 'search' ], presetState: PresetState ): void {
	const { searchFlags, source, perPage, searchPhrase, filters, view } = action;
	const preset = presetState.presets.find( ( pres ) => pres.id === presetState.currentPreset );

	const defaults = {
		searchPhrase: '',
		searchFlags: [ 'case' ],
		source: [ 'post', 'page' ],
		perPage: 25,
		sub: 'search',
		filters: [],
		view: [],
	};

	if ( preset ) {
		setPageUrl( { page: 'search-regex.php', preset: preset.id }, {} );
	} else {
		setPageUrl(
			{
				page: 'search-regex.php',
				searchPhrase,
				searchFlags,
				source,
				perPage,
				filters: filterToQuery( filters ),
				view: view.join( ',' ),
			},
			defaults
		);
	}
}

function getDataFormat( results: unknown[], format: string ): string {
	if ( format === 'json' ) {
		return JSON.stringify( results );
	}

	return results.join( '\n' );
}

function getDataFilename( format: string ): string {
	if ( format === 'json' ) {
		return 'export.json';
	}

	if ( format === 'csv' ) {
		return 'export.csv';
	}

	if ( format === 'sql' ) {
		return 'export.sql';
	}

	return 'export.txt';
}

function saveExport( results: unknown[], format: string ): void {
	const data = getDataFormat( results, format );
	const filename = getDataFilename( format );

	saveAs( new Blob( [ data ] ), filename );
}

export const urlMiddleware: Middleware = ( store ) => ( next ) => ( action: any ) => {
	switch ( action.type ) {
		case SEARCH_COMPLETE:
			const { search, results } = ( store.getState() as RootState ).search;

			if (
				( action as SearchCompleteAction ).isSave &&
				( action as SearchCompleteAction ).progress.next === false &&
				search.action === 'export'
			) {
				saveExport(
					[ ...results, ...( action as SearchCompleteAction ).results ],
					search.actionOption?.format ? search.actionOption.format : 'json'
				);
			}

			break;

		case SEARCH_START_FRESH:
			setUrlForPage(
				action as SearchAction,
				( store.getState() as RootState ).search,
				( store.getState() as RootState ).preset
			);
			break;

		case PRESET_SELECT:
			const presetAction = action as PresetSelectAction;
			if ( presetAction.currentOnly ) {
				break;
			}

			if ( presetAction.preset ) {
				setPageUrl( { page: 'search-regex.php', preset: presetAction.preset.id }, getPageUrl() );
			} else {
				removeFromPageUrl( 'preset' );
			}
			break;
	}

	return next( action );
};
