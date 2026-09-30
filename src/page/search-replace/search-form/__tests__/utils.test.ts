import { getReplacementInputValue, getSimpleReplacement } from '../utils';

describe( 'getSimpleReplacement', () => {
	it( 'preserves the null value used by the remove option', () => {
		expect( getSimpleReplacement( { replacement: null } ) ).toBeNull();
	} );

	it( 'keeps an empty replacement distinct from the remove option', () => {
		expect( getSimpleReplacement( { replacement: '' } ) ).toBe( '' );
	} );
} );

describe( 'getReplacementInputValue', () => {
	it( 'keeps an empty input distinct from the remove option', () => {
		expect( getReplacementInputValue( '' ) ).toBe( '' );
	} );

	it( 'preserves the null value used by the remove option', () => {
		expect( getReplacementInputValue( null ) ).toBeNull();
	} );
} );
