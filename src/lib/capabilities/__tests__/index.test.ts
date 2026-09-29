import { CAP_SEARCHREGEX_SEARCH, CAP_SEARCHREGEX_OPTIONS, CAP_SEARCHREGEX_SUPPORT } from '../index';

describe( 'capabilities constants', () => {
	it( 'match the real WordPress capability names used by the PHP Capabilities class', () => {
		expect( CAP_SEARCHREGEX_SEARCH ).toBe( 'search_regex_manage' );
		expect( CAP_SEARCHREGEX_OPTIONS ).toBe( 'search_regex_options' );
		expect( CAP_SEARCHREGEX_SUPPORT ).toBe( 'search_regex_support' );
	} );
} );
