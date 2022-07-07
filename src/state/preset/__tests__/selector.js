/**
 * Internal dependencies
 */
import { getDefaultPresetValues } from '../selector';

describe( 'preset selectors', () => {
	describe( 'default tag values', () => {
		test( 'return null if no preset', () => {
			expect( getDefaultPresetValues( null ) ).toBe( null );
		} );

		test( 'return same values if no tags present in search values', () => {
			const source = { tags: [ { name: 'CAT' } ], search: { searchPhrase: 'no tag', replacement: 'no tag' } };

			expect( getDefaultPresetValues( source ) ).toStrictEqual( source.search );
		} );

		test( 'return same values if no tags', () => {
			const source = { tags: [], search: { searchPhrase: 'no tag', replacement: 'no tag' } };

			expect( getDefaultPresetValues( source ) ).toStrictEqual( source.search );
		} );

		test( 'return search phrases with tags removed', () => {
			const source = {
				tags: [ { name: 'CAT' } ],
				search: { searchPhrase: 'no CAT', replacement: 'no CAT' },
			};

			expect( getDefaultPresetValues( source ) ).toEqual( {
				searchPhrase: 'no ',
				replacement: 'no ',
			} );
		} );
	} );
} );
