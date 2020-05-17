import getPreload from '../index';

describe( 'preload', () => {
	test( 'preload returns default', () => {
		expect( getPreload( 'api', { test: 1 } ) ).toStrictEqual( { test: 1 } );
	} );
} );
