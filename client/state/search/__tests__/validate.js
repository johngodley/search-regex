/**
 * Internal dependencies
 */

import getValidatedSearch from 'state/search/validate';

const SOURCES = [
	{
		name: 'core',
		sources: [
			{
				name: 'comments',
			},
		],
	},
];
const SOURCE_FLAGS = { comments: { guid: 'GUID' } };

const getSearch = ( flags = {} ) => ( {
	source: [],
	searchFlags: [],
	perPage: 25,
	replacement: '',
	searchPhrase: '',
	...flags,
} );

const getExpected = ( flags = {} ) => ( {
	...getSearch(),
	searchFlags: [],
	...flags,
} );

describe( 'search validate', () => {
	test( 'valid empty search returns empty search', () => {
		const search = getSearch();
		const expected = getExpected();

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'invalid per page', () => {
		const search = getSearch( { perPage: 13 } );
		const expected = getExpected();

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'valid per page', () => {
		const search = getSearch( { perPage: 50 } );
		const expected = getExpected( { perPage: 50 } );

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'invalid search flag', () => {
		const search = getSearch( { searchFlags: [ 'cats', 'dogs' ] } );
		const expected = getExpected();

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'valid search flags', () => {
		const search = getSearch( { searchFlags: [ 'case', 'regex' ] } );
		const expected = getExpected( { searchFlags: [ 'case', 'regex' ] } );

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'invalid source', () => {
		const search = getSearch( { source: [ 'cats' ] } );
		const expected = getExpected();

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );

	test( 'valid sources', () => {
		const search = getSearch( { source: [ 'comments' ] } );
		const expected = getExpected( { source: [ 'comments' ] } );

		expect( getValidatedSearch( search, SOURCES, SOURCE_FLAGS ) ).toEqual( expected );
	} );
} );
