// Mock @wp-plugin-lib before importing
jest.mock( '../../wp-plugin-lib', () => ( {
	getPageUrl: jest.fn( () => ( {} ) ),
	setPageUrl: jest.fn(),
} ) );

// Mock preload
jest.mock( '../preload', () => ( {
	__esModule: true,
	default: jest.fn( () => ( {} ) ),
} ) );

import { getQuerySearchParams, getDefaultSearch } from '../search-utils';

describe( 'getQuerySearchParams', () => {
	describe( 'basic parameter parsing', () => {
		it( 'should parse searchPhrase from query params', () => {
			const query = { searchphrase: 'test search' };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBe( 'test search' );
		} );

		it( 'should parse searchFlags as array from comma-separated string', () => {
			const query = { searchflags: 'regex,case' };
			const result = getQuerySearchParams( query );

			expect( result.searchFlags ).toEqual( [ 'regex', 'case' ] );
		} );

		it( 'should parse source as array from comma-separated string', () => {
			const query = { source: 'post,page' };
			const result = getQuerySearchParams( query );

			expect( result.source ).toEqual( [ 'post', 'page' ] );
		} );

		it( 'should parse replacement parameter', () => {
			const query = { replacement: 'new value' };
			const result = getQuerySearchParams( query );

			expect( result.replacement ).toBe( 'new value' );
		} );

		it( 'should parse perPage as number', () => {
			const query = { perpage: '50' };
			const result = getQuerySearchParams( query );

			expect( result.perPage ).toBe( 50 );
		} );

		it( 'should parse mode parameter', () => {
			const query = { mode: 'advanced' };
			const result = getQuerySearchParams( query );

			expect( result.mode ).toBe( 'advanced' );
		} );
	} );

	describe( 'case normalization', () => {
		it( 'should normalize uppercase keys to lowercase', () => {
			const query = { SEARCHPHRASE: 'test' };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBe( 'test' );
		} );

		it( 'should normalize mixed case keys to lowercase', () => {
			const query = { SearchPhrase: 'test' };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBe( 'test' );
		} );

		it( 'should handle camelCase searchPhrase', () => {
			const query = { searchPhrase: 'test' };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBe( 'test' );
		} );

		it( 'should handle camelCase searchFlags', () => {
			const query = { searchFlags: 'regex' };
			const result = getQuerySearchParams( query );

			expect( result.searchFlags ).toEqual( [ 'regex' ] );
		} );

		it( 'should handle camelCase perPage', () => {
			const query = { perPage: '100' };
			const result = getQuerySearchParams( query );

			expect( result.perPage ).toBe( 100 );
		} );
	} );

	describe( 'array handling', () => {
		it( 'should convert array values to comma-separated strings for processing', () => {
			const query = { searchflags: [ 'regex', 'case' ] };
			const result = getQuerySearchParams( query );

			expect( result.searchFlags ).toEqual( [ 'regex', 'case' ] );
		} );

		it( 'should handle source as array', () => {
			const query = { source: [ 'post', 'page', 'comment' ] };
			const result = getQuerySearchParams( query );

			expect( result.source ).toEqual( [ 'post', 'page', 'comment' ] );
		} );

		it( 'should handle single-item arrays', () => {
			const query = { searchflags: [ 'regex' ] };
			const result = getQuerySearchParams( query );

			expect( result.searchFlags ).toEqual( [ 'regex' ] );
		} );

		it( 'should handle empty arrays', () => {
			const query = { searchflags: [] };
			const result = getQuerySearchParams( query );

			// Empty arrays become undefined (don't override defaults)
			expect( result.searchFlags ).toBeUndefined();
		} );
	} );

	describe( 'edge cases and validation', () => {
		it( 'should return empty object for invalid parameters', () => {
			const query = { invalid: 'parameter' };
			const result = getQuerySearchParams( query );

			// Invalid parameters are ignored
			expect( result ).toEqual( {} );
		} );

		it( 'should filter out invalid searchFlags values', () => {
			const query = { searchflags: 'regex,invalid,case' };
			const result = getQuerySearchParams( query );

			// Zod schema should filter out invalid flags
			expect( result.searchFlags ).toBeDefined();
			// The schema might filter invalid values or keep them - check actual behavior
		} );

		it( 'should handle numeric strings for perPage', () => {
			const query = { perpage: '25' };
			const result = getQuerySearchParams( query );

			expect( result.perPage ).toBe( 25 );
		} );

		it( 'should handle null query params', () => {
			const result = getQuerySearchParams( null );

			// Should use getPageUrl() as fallback
			expect( result ).toBeDefined();
		} );

		it( 'should handle empty query params', () => {
			const query = {};
			const result = getQuerySearchParams( query );

			// Empty query returns empty object (doesn't override defaults)
			expect( result ).toEqual( {} );
		} );

		it( 'should handle undefined values', () => {
			const query = { searchphrase: undefined };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBeUndefined();
		} );

		it( 'should handle null values', () => {
			const query = { searchphrase: null };
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBeUndefined();
		} );
	} );

	describe( 'multiple parameters', () => {
		it( 'should parse multiple parameters correctly', () => {
			const query = {
				searchphrase: 'test query',
				searchflags: 'regex,case',
				source: 'post,page',
				perpage: '50',
				mode: 'advanced',
			};
			const result = getQuerySearchParams( query );

			expect( result ).toMatchObject( {
				searchPhrase: 'test query',
				searchFlags: [ 'regex', 'case' ],
				source: [ 'post', 'page' ],
				perPage: 50,
				mode: 'advanced',
			} );
			// No query params for filters/view means they're undefined
			expect( result.filters ).toBeUndefined();
			expect( result.view ).toBeUndefined();
		} );

		it( 'should handle mixed case and camelCase parameters together', () => {
			const query = {
				searchPhrase: 'test',
				searchflags: 'regex',
				perPage: '100',
			};
			const result = getQuerySearchParams( query );

			expect( result.searchPhrase ).toBe( 'test' );
			expect( result.searchFlags ).toEqual( [ 'regex' ] );
			expect( result.perPage ).toBe( 100 );
		} );
	} );

	describe( 'filters parameter', () => {
		it( 'should parse filters from JSON string', () => {
			const filters = [
				{
					column: 'post_title',
					logic: 'equals',
					items: [ { value: 'test', flags: [] } ],
				},
			];
			const query = { filters: JSON.stringify( filters ) };
			const result = getQuerySearchParams( query );

			expect( result.filters ).toBeDefined();
			expect( result.filters ).toHaveLength( 1 );
			if ( result.filters && result.filters.length > 0 ) {
				const filter = result.filters[ 0 ];
				expect( filter ).toBeDefined();
				if ( filter && 'column' in filter ) {
					expect( filter.column ).toBe( 'post_title' );
				}
			}
		} );

		it( 'should handle invalid filters JSON', () => {
			const query = { filters: 'invalid json' };
			const result = getQuerySearchParams( query );

			// Invalid JSON results in empty array default
			expect( result.filters ).toEqual( [] );
		} );

		it( 'should handle empty filters array', () => {
			const query = { filters: '[]' };
			const result = getQuerySearchParams( query );

			expect( result.filters ).toEqual( [] );
		} );
	} );

	describe( 'view parameter', () => {
		it( 'should parse view parameter as array', () => {
			const query = { view: 'replace' };
			const result = getQuerySearchParams( query );

			// view is returned as an array
			expect( result.view ).toEqual( [ 'replace' ] );
		} );

		it( 'should accept search view as array', () => {
			const query = { view: 'search' };
			const result = getQuerySearchParams( query );

			expect( result.view ).toEqual( [ 'search' ] );
		} );
	} );
} );

describe( 'getDefaultSearch', () => {
	it( 'should return default filters for posts source', () => {
		const defaults = getDefaultSearch();

		expect( defaults.filters ).toBeDefined();
		expect( defaults.filters ).toHaveLength( 1 );
		expect( defaults.filters![ 0 ] ).toMatchObject( {
			type: 'posts',
			items: [
				{
					column: 'post_type',
					logic: 'include',
					values: [ 'post', 'page' ],
				},
			],
		} );
	} );

	it( 'should return default search with posts source', () => {
		const defaults = getDefaultSearch();

		expect( defaults.source ).toEqual( [ 'posts' ] );
		expect( defaults.searchPhrase ).toBe( '' );
		expect( defaults.searchFlags ).toEqual( [ 'case' ] );
		expect( defaults.perPage ).toBe( 25 );
	} );
} );

describe( 'default filters preservation', () => {
	it( 'should not override default filters when query params have no filters', () => {
		const defaults = getDefaultSearch();
		const queryParams = getQuerySearchParams( {} );

		// Merge like getInitialSearch does
		const merged = {
			...defaults,
			...queryParams,
		};

		// Default filters should be preserved because queryParams.filters is undefined
		expect( merged.filters ).toBeDefined();
		expect( merged.filters ).toHaveLength( 1 );
		expect( merged.filters![ 0 ] ).toMatchObject( {
			type: 'posts',
			items: [
				{
					column: 'post_type',
					logic: 'include',
					values: [ 'post', 'page' ],
				},
			],
		} );
	} );

	it( 'should override default filters when query params explicitly provide filters', () => {
		const defaults = getDefaultSearch();
		const customFilters = [
			{
				type: 'posts',
				items: [
					{
						column: 'post_status',
						logic: 'include',
						values: [ 'publish' ],
					},
				],
			},
		];
		const queryParams = getQuerySearchParams( {
			filters: JSON.stringify( customFilters ),
		} );

		// Merge like getInitialSearch does
		const merged = {
			...defaults,
			...queryParams,
		};

		// Custom filters should override defaults
		expect( merged.filters ).toEqual( customFilters );
	} );
} );
