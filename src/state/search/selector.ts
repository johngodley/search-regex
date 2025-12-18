import { __ } from '@wordpress/i18n';
import type {
	SearchValues,
	Schema,
	SchemaColumn,
	Filter,
	ResultColumn,
	ModifyColumn,
	SelectOption,
} from '../../types/search';
import { STATUS_COMPLETE, STATUS_FAILED } from '../settings/type';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from '../search/type';
import { getPageUrl } from '@wp-plugin-lib';
import { getDefaultPresetValues } from '../preset/selector';

interface PerPageOption {
	value: number;
	label: string;
}

interface SearchFlagOption {
	value: string;
	label: string;
	alt: string;
}

export const getAvailableSearchFlags = (): SearchFlagOption[] => [
	{
		value: 'regex',
		label: __( 'Regular Expression', 'search-regex' ),
		alt: __( 'Regex', 'search-regex' ),
	},
	{
		value: 'case',
		label: __( 'Ignore Case', 'search-regex' ),
		alt: __( 'Case', 'search-regex' ),
	},
	{
		value: 'multi',
		label: __( 'Multiline', 'search-regex' ),
		alt: __( 'Multi', 'search-regex' ),
	},
];

export const getAvailablePerPage = (): PerPageOption[] => [
	{
		value: 25,
		label: __( '25 per page', 'search-regex' ),
	},
	{
		value: 50,
		label: __( '50 per page', 'search-regex' ),
	},
	{
		value: 100,
		label: __( '100 per page', 'search-regex' ),
	},
	{
		value: 250,
		label: __( '250 per page', 'search-regex' ),
	},
	{
		value: 500,
		label: __( '500 per page', 'search-regex' ),
	},
	{
		value: 1000,
		label: __( '1000 per page', 'search-regex' ),
	},
	{
		value: 2000,
		label: __( '2000 per page', 'search-regex' ),
	},
];

export const isAlreadyFinished = ( state: { status: string | null } ): boolean =>
	state.status === STATUS_COMPLETE || state.status === null || state.status === STATUS_FAILED;

export function getReplaceTotal(
	state: { totals: { rows: number } },
	action: { totals: { matched_rows?: number; rows?: number } }
): number {
	if ( action.totals.matched_rows ) {
		return action.totals.matched_rows;
	}

	if ( action.totals.rows ) {
		return action.totals.rows;
	}

	return state.totals.rows;
}

export function isComplete(
	action: { progress: { next: boolean | number; previous: boolean | number } },
	_results: unknown[],
	direction: string
): boolean {
	if ( direction === SEARCH_FORWARD && action.progress.next === false ) {
		return true;
	}

	if ( direction === SEARCH_BACKWARD && action.progress.previous === false ) {
		return true;
	}

	return false;
}

export function isAdvancedSearch( search: SearchValues ): boolean {
	const { searchFlags, searchPhrase, filters } = search;

	if ( searchFlags && searchPhrase && searchFlags.indexOf( 'regex' ) !== -1 && searchPhrase.length > 0 ) {
		return true;
	}

	if ( ! filters ) {
		return false;
	}

	for ( let index = 0; index < filters.length; index++ ) {
		const filter = filters[ index ];

		for ( let itemIndex = 0; itemIndex < filter.items.length; itemIndex++ ) {
			const item = filter.items[ itemIndex ];

			if ( item.flags && item.flags.indexOf( 'regex' ) !== -1 ) {
				return true;
			}
		}
	}

	return false;
}

export function getSearchValues(
	values: SearchValues & { limit?: number | null }
): SearchValues & { limit?: number | null } {
	return {
		...values,
		replacement: getReplacement( values.replacement ?? '' ) ?? undefined,
		perPage: values.perPage === -1 ? 250 : values.perPage,
		...( values.perPage === -1 && values.limit ? { limit: null } : {} ),
	};
}

export function getReplacement( replacement: string ): string | null {
	if ( replacement === '' ) {
		return null;
	}

	if ( replacement === null ) {
		return '';
	}

	return replacement;
}

export function getDefaultSearch(): SearchValues {
	return {
		searchPhrase: '',
		searchFlags: [ 'case' ],
		replacement: '',
		source: [ 'posts' ],
		perPage: 25,
		filters: getDefaultFilters( 'posts' ),
		action: 'replace',
		actionOption: [],
		view: [],
	} as SearchValues;
}

export function getDefaultFilters( source: string ): Filter[] {
	if ( source === 'posts' ) {
		return [
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
		];
	}

	if ( source === 'comment' ) {
		return [
			{
				type: 'comment',
				items: [
					{
						column: 'comment_approved',
						logic: 'exclude',
						values: [ 'spam' ],
					},
				],
			},
		];
	}

	return [];
}

export function getQuerySearchParams( queryParams: Record< string, any > | null = null ): Partial< SearchValues > {
	const query = queryParams ? queryParams : getPageUrl();
	const search: Record< string, any > = {};
	const params: Record< string, string > = {
		searchphrase: 'searchPhrase',
		searchflags: 'searchFlags',
		source: 'source',
		replacement: 'replacement',
		perpage: 'perPage',
		filters: 'filters',
		view: 'view',
	};

	Object.keys( params ).forEach( ( key ) => {
		if ( query[ key ] ) {
			search[ params[ key ] ] = query[ key ];
		}
	} );

	if ( search.filters ) {
		try {
			search.filters = JSON.parse( search.filters );
		} catch ( error ) {
			search.filters = [];
		}
	}

	if ( search.view ) {
		search.view = search.view.split( ',' );
	}

	return search;
}

export function getSchema( schemas: Schema[], source: string ): Schema | undefined {
	return schemas.find( ( scheme ) => scheme.type === source );
}

export function getSchemaColumn( columns: SchemaColumn[], name: string ): SchemaColumn | undefined {
	return columns.find( ( scheme ) => scheme.column === name );
}

export function getSchemaSourceColumn( schemas: Schema[], source: string, column: string ): SchemaColumn | null {
	const schemaSource = getSchema( schemas, source );

	if ( schemaSource ) {
		return getSchemaColumn( schemaSource.columns, column ) || null;
	}

	return null;
}

export function getSearchFromPreset( preset: { search: SearchValues } | null | undefined ): SearchValues {
	if ( preset ) {
		return {
			...preset.search,
			...getDefaultPresetValues( preset as any ),
		};
	}

	return getDefaultSearch();
}

export function getFilterForType( type: string, schema: Schema ): Filter {
	const first = schema.columns[ 0 ];

	return {
		type,
		items: [ { column: first.column ?? '' } ],
	};
}

export function getLabel(
	labels: Array< { value: string; label: string } >,
	labelId: string,
	labelValue?: string
): string {
	for ( let index = 0; index < labels.length; index++ ) {
		if ( labels[ index ].value === labelId ) {
			return labels[ index ].label;
		}
	}

	return labelValue || labelId;
}

export function getSearchOptionsForSources( sources: string[], schema: Schema[] ): SelectOption[] {
	return sources.map( ( sourceName ) => ( {
		label: schema.find( ( scheme ) => scheme.type === sourceName )?.name || '',
		value: sourceName,
	} ) );
}

function getActionDefaultsResult( column: ResultColumn, schema: SchemaColumn | null ): Record< string, any > {
	if ( ! schema ) {
		return {};
	}

	if ( schema.type === 'member' ) {
		return {
			values: ( column.contexts as any[] )
				.filter( ( item ) => item.type !== 'empty' )
				.map( ( item ) => ( item.replacement_value ? item.replacement_value : item.value ) ),
		};
	}

	return {};
}

function getActionDefaults( schema: SchemaColumn ): Record< string, any > {
	if ( schema.type === 'member' && Array.isArray( schema.options ) ) {
		return { values: [ schema.options[ 0 ].value ] };
	}

	return {};
}

export function getNewAction( columnId: string, schema: Schema ): ModifyColumn {
	const columnSchema = schema.columns ? getSchemaColumn( schema.columns, columnId ) : schema;
	const defaults = columnSchema ? getActionDefaults( columnSchema as SchemaColumn ) : {};

	return {
		column: ( columnSchema as SchemaColumn ).column,
		operation: '',
		source: schema.type,
		...defaults,
	} as unknown as ModifyColumn;
}

export function getNewActionFromResult(
	column: ResultColumn,
	columnSchema: SchemaColumn | null,
	source: string
): ModifyColumn {
	const defaults = getActionDefaultsResult( column, columnSchema );

	return { column: columnSchema?.column || '', operation: '', source, ...defaults } as unknown as ModifyColumn;
}
