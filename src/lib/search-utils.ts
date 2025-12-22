import { __ } from '@wordpress/i18n';
import type {
	SearchValues,
	Schema,
	SchemaColumn,
	Filter,
	ResultColumn,
	ModifyColumn,
	SelectOption,
	SearchSourceGroup,
} from '../types/search';
import { STATUS_COMPLETE, STATUS_FAILED, SEARCH_FORWARD, SEARCH_BACKWARD } from './constants';
import { getPageUrl } from '@wp-plugin-lib';
import { getDefaultPresetValues } from './preset-utils';
import getPreload from './preload';
import { queryParamsSchema, searchValuesSchema } from './api-schemas';

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
		if ( ! filter || ! filter.items ) {
			continue;
		}

		for ( let itemIndex = 0; itemIndex < filter.items.length; itemIndex++ ) {
			const item = filter.items[ itemIndex ];
			if ( ! item ) {
				continue;
			}

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
	const result: SearchValues & { limit?: number | null } = {};

	// Copy defined properties only
	if ( values.searchPhrase !== undefined ) {
		result.searchPhrase = values.searchPhrase;
	}
	if ( values.searchFlags !== undefined ) {
		result.searchFlags = values.searchFlags;
	}
	if ( values.source !== undefined ) {
		result.source = values.source;
	}
	if ( values.filters !== undefined ) {
		result.filters = values.filters;
	}

	// Handle perPage
	if ( values.perPage !== undefined ) {
		result.perPage = values.perPage === -1 ? 250 : values.perPage;
	}

	// Handle replacement
	const replacement = getReplacement( values.replacement ?? '' );
	if ( replacement !== null ) {
		result.replacement = replacement;
	} else if ( values.replacement !== undefined && values.replacement !== '' ) {
		// Only set if it was explicitly set to something other than empty string
		result.replacement = values.replacement;
	}

	// Handle limit
	if ( values.perPage === -1 && values.limit !== undefined ) {
		result.limit = null;
	} else if ( values.limit !== undefined ) {
		result.limit = values.limit;
	}

	return result;
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

	// Normalize query params to lowercase (WordPress may send mixed case)
	// Also handle both camelCase and lowercase versions
	// Note: getPageUrl() already parses arrays, so we need to serialize them back to strings for Zod
	const normalizedQuery: Record< string, any > = {};
	for ( const key in query ) {
		const lowerKey = key.toLowerCase();
		let value = query[ key ];

		// Serialize arrays back to comma-separated strings for Zod validation
		// (Zod schema expects strings and transforms them to arrays)
		if ( Array.isArray( value ) ) {
			value = value.join( ',' );
		}

		// Preserve the value, but use lowercase key
		normalizedQuery[ lowerKey ] = value;
		// Also check for camelCase versions
		if ( key === 'searchPhrase' && ! normalizedQuery.searchphrase ) {
			normalizedQuery.searchphrase = Array.isArray( query[ key ] ) ? query[ key ].join( ',' ) : query[ key ];
		}
		if ( key === 'searchFlags' && ! normalizedQuery.searchflags ) {
			normalizedQuery.searchflags = Array.isArray( query[ key ] ) ? query[ key ].join( ',' ) : query[ key ];
		}
		if ( key === 'perPage' && ! normalizedQuery.perpage ) {
			normalizedQuery.perpage = Array.isArray( query[ key ] ) ? query[ key ].join( ',' ) : query[ key ];
		}
	}

	// ✨ Validate and transform URL parameters with Zod
	const validated = queryParamsSchema.safeParse( normalizedQuery );

	if ( ! validated.success ) {
		// If validation fails, return empty object (fallback to defaults)
		return {};
	}

	const parsed = validated.data;

	// Map transformed query param values to SearchValues property names
	const result: Partial< SearchValues > = {};

	if ( parsed.searchphrase ) {
		result.searchPhrase = parsed.searchphrase;
	}
	if ( parsed.searchflags && parsed.searchflags.length > 0 ) {
		result.searchFlags = parsed.searchflags;
	}
	if ( parsed.source && parsed.source.length > 0 ) {
		result.source = parsed.source;
	}
	if ( parsed.replacement ) {
		result.replacement = parsed.replacement;
	}
	if ( parsed.perpage !== undefined ) {
		result.perPage = parsed.perpage;
	}
	if ( parsed.filters && parsed.filters.length > 0 ) {
		result.filters = parsed.filters as Filter[];
	}
	if ( parsed.view && parsed.view.length > 0 ) {
		result.view = parsed.view;
	}

	return result;
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
	if ( ! first ) {
		return {
			type,
			items: [ { column: '' } ],
		};
	}

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
		const label = labels[ index ];
		if ( label && label.value === labelId ) {
			return label.label;
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
	if ( schema.type === 'member' && Array.isArray( schema.options ) && schema.options.length > 0 ) {
		const firstOption = schema.options[ 0 ];
		if ( firstOption ) {
			return { values: [ firstOption.value ] };
		}
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

// Validation functions

function sanitizeValue(
	value: number | string,
	array: Array< { value: number | string; name?: string } >,
	defaultValue: number | string
): number | string {
	if ( array.find( ( item ) => item.value === value || item.name === value ) ) {
		return value;
	}

	return defaultValue;
}

function sanitizeArray( valueArray: string[], array: Array< { value: string; name?: string } > ): string[] {
	return valueArray.filter( ( item ) => sanitizeValue( item, array, '' ) );
}

function sanitizeSources( sourceArray: SearchSourceGroup[], array: string[] ): string[] {
	let sources: string[] = [];

	sourceArray.forEach( ( source ) => {
		sources = [ ...sources, ...source.sources.map( ( item ) => item.name ) ];
	} );

	return array.filter( ( source ) => sources.indexOf( source ) !== -1 );
}

function sanitizeFilters( filters: Filter[], schema: Schema[], source: string[] ): Filter[] {
	return filters.filter( ( filter ) => {
		const found = getSchema( schema, filter.type );

		if ( found && source.indexOf( found.type ) !== -1 ) {
			return ( filter.items.filter( ( itemFilter ) => {
				return found.columns.find( ( finding ) => finding.column === itemFilter.column ) !== undefined;
			} ).length = filter.items.length );
		}

		return false;
	} );
}

function sanitizeView( view: string[] | unknown, schema: Schema[] ): string[] {
	if ( ! Array.isArray( view ) ) {
		return [];
	}

	return view.filter( ( item ) => {
		const parts = item.split( '__' );

		if ( parts.length === 2 ) {
			const found = getSchema( schema, parts[ 0 ] );

			if ( found ) {
				return found.columns.find( ( finding ) => finding.column === parts[ 1 ] ) !== undefined;
			}
		}

		return false;
	} );
}

export default function getValidatedSearch(
	search: SearchValues & { filters: Filter[] },
	initialSources: SearchSourceGroup[] | null = null,
	initialSchema: Schema[] | null = null
): SearchValues {
	const sources = initialSources ? initialSources : getPreload< SearchSourceGroup[] >( 'sources', [] );
	const schema = initialSchema ? initialSchema : getPreload< Schema[] >( 'schema', [] );

	// ✨ Validate search values structure with Zod (non-strict, allows extra fields)
	const validated = searchValuesSchema.safeParse( search );
	const searchData = validated.success ? validated.data : search;

	const { searchPhrase, searchFlags, replacement, perPage, view, action, actionOption } = searchData;
	const source = sanitizeSources(
		sources,
		searchData.source && searchData.source.length > 0 ? searchData.source : []
	);

	// Handle perPage conversion safely
	let validatedPerPage: number;
	if ( typeof perPage === 'number' ) {
		validatedPerPage = sanitizeValue( perPage, getAvailablePerPage(), 25 ) as number;
	} else if ( typeof perPage === 'string' ) {
		const parsed = parseInt( perPage, 10 );
		validatedPerPage = sanitizeValue( isNaN( parsed ) ? 25 : parsed, getAvailablePerPage(), 25 ) as number;
	} else {
		validatedPerPage = 25;
	}

	return {
		searchPhrase,
		searchFlags: sanitizeArray( searchFlags ?? [], getAvailableSearchFlags() ),
		source,
		replacement,
		perPage: validatedPerPage,
		filters: sanitizeFilters( ( searchData.filters as Filter[] ) ?? [], schema, source ),
		view: sanitizeView( view, schema ),
		action,
		actionOption,
	} as SearchValues;
}
