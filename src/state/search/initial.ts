import type { SearchState, SearchSourceGroup, Schema } from '../../types/search';
import getPreload from '../../lib/preload';
import { getQuerySearchParams, getDefaultSearch, getSearchFromPreset } from './selector';
import getValidatedSearch from './validate';
import { getPageUrl } from '@wp-plugin-lib';

declare const SearchRegexi10n: {
	settings: {
		defaultPreset: string;
	};
};

interface PresetValue {
	id: string;
	[ key: string ]: unknown;
}

export function getInitialSearch(): SearchState & {
	resultsDirty: boolean;
	replacing: unknown[];
	labels: unknown[];
	isSaving: boolean;
} {
	const query = getPageUrl();
	const sources = getPreload( 'sources', [] );
	const search = getValidatedSearch( {
		...getDefaultSearch(),
		...getSearchFromPreset(
			getPreload< PresetValue[] >( 'presets', [] ).find(
				( item ) => item.id === query.preset || item.id === SearchRegexi10n.settings.defaultPreset
			) as any
		),
		...getQuerySearchParams(),
	} as any );

	return {
		results: [],
		resultsDirty: false,
		replacing: [],

		search,

		searchDirection: null,

		labels: getPreload( 'labels', [] ),

		requestCount: 0,
		totals: {
			matched_rows: 0,
			rows: 0,
		},
		progress: {},

		status: null,
		showLoading: false,
		isSaving: false,

		sources: sources as unknown as SearchSourceGroup,

		canCancel: false,

		schema: getPreload< Schema[] >( 'schema', [] ),
		replaceAll: false,
	};
}
