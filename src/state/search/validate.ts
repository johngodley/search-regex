import type { SearchValues, SearchSourceGroup, Schema, Filter } from '../../types/search';
import { getAvailableSearchFlags, getAvailablePerPage, getSchema } from './selector';
import getPreload from '../../lib/preload';

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
	const { searchPhrase, searchFlags, replacement, perPage, view, action, actionOption } = search;
	const source = sanitizeSources( sources, search.source && search.source.length > 0 ? search.source : [] );

	return {
		searchPhrase,
		searchFlags: sanitizeArray( searchFlags ?? [], getAvailableSearchFlags() ),
		source,
		replacement,
		perPage: sanitizeValue( parseInt( perPage as any, 10 ), getAvailablePerPage(), 25 ) as number,
		filters: sanitizeFilters( search.filters, schema, source ),
		view: sanitizeView( view, schema ),
		action,
		actionOption,
	} as SearchValues;
}
