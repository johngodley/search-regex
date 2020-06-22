/**
 * Internal dependencies
 */
import {
	isAlreadyFinished,
	hasReplaceFinished,
	isComplete,
	getSearchValues,
	getReplacement,
} from 'state/search/selector';
import { STATUS_COMPLETE, STATUS_IN_PROGRESS } from 'state/settings/type';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';

describe( 'search selectors', () => {
	describe( 'isAlreadyFinished', () => {
		test( 'isAlreadyFinished returns true if status complete', () => {
			expect( isAlreadyFinished( { status: STATUS_COMPLETE } ) ).toBe( true );
		} );

		test( 'isAlreadyFinished returns true if status is null', () => {
			expect( isAlreadyFinished( { status: null } ) ).toBe( true );
		} );

		test( 'isAlreadyFinished returns false if status is something else', () => {
			expect( isAlreadyFinished( { status: STATUS_IN_PROGRESS } ) ).toBe( false );
		} );
	} );

	describe( 'isComplete', () => {
		const getAction = ( progress ) => ( { progress } );

		test( 'going forward and no next then it is complete', () => {
			expect( isComplete( getAction( { next: false } ), [], SEARCH_FORWARD ) ).toBe( true );
		} );

		test( 'going forward and has a next then it is not complete', () => {
			expect( isComplete( getAction( { next: true } ), [], SEARCH_FORWARD ) ).toBe( false );
		} );

		test( 'going backward and no previous then it is complete', () => {
			expect( isComplete( getAction( { previous: false } ), [], SEARCH_BACKWARD ) ).toBe( true );
		} );

		test( 'going backward and has a previous then it is not complete', () => {
			expect( isComplete( getAction( { previous: true } ), [], SEARCH_BACKWARD ) ).toBe( false );
		} );
	} );

	describe( 'getSearchValues', () => {
		const SOURCES = [
			{
				name: 'posttype',
				sources: [
					{
						name: 'post',
					},
				],
			},
		];

		test( 'post types are removed if all posts are searched', () => {
			const search = { source: [ 'posts', 'post' ], searchFlags: [], sourceFlags: [], replacement: '' };
			const expected = { source: [ 'posts' ], searchFlags: [], sourceFlags: [], replacement: '' };

			expect( getSearchValues( search, {}, SOURCES ) ).toStrictEqual( expected );
		} );

		test( 'null replacement is converted to empty string', () => {
			const search = { source: [ 'post' ], searchFlags: [], sourceFlags: [], replacement: null };
			const expected = { source: [ 'post' ], searchFlags: [], sourceFlags: [], replacement: '' };

			expect( getSearchValues( search, {}, SOURCES ) ).toStrictEqual( expected );
		} );
	} );

	describe( 'getReplacement', () => {
		test( 'null replacement is converted to empty string', () => {
			expect( getReplacement( null ) ).toBe( '' );
		} );

		test( 'any other replacement is returned as-is', () => {
			expect( getReplacement( 'cat' ) ).toBe( 'cat' );
		} );

		test( 'empty string is empty string', () => {
			expect( getReplacement( '' ) ).toBe( '' );
		} );
	} );
} );
