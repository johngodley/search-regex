/**
 * Internal dependencies
 */

import {
	getAllPostTypes,
	removePostTypes,
	SOURCE_TYPE_POSTS,
} from 'lib/sources';

describe( 'Source', () => {
	const sourcesWithPostTypes = [ { name: SOURCE_TYPE_POSTS, sources: [ { name: 'cats' }, { name: 'dogs' } ] } ];
	const sourcesWithoutPostTypes = [ { name: 'cats' } ];

	test( 'getAllPostTypes returns empty array when no sources', () => {
		expect( getAllPostTypes( [] ) ).toEqual( [] );
	} );

	test( 'getAllPostTypes returns empty array when no post types', () => {
		expect( getAllPostTypes( sourcesWithoutPostTypes ) ).toEqual( [] );
	} );

	test( 'getAllPostTypes returns post types', () => {
		expect( getAllPostTypes( sourcesWithPostTypes ) ).toEqual( [ 'cats', 'dogs' ] );
	} );

	test( 'removePostTypes does not change anything if posts source not present', () => {
		expect( removePostTypes( [ 'cats', 'dogs' ], sourcesWithoutPostTypes ) ).toEqual( [ 'cats', 'dogs' ] );
	} );

	test( 'removePostTypes removes post types', () => {
		expect( removePostTypes( [ 'cats', 'dogs', 'posts' ], sourcesWithPostTypes ) ).toEqual( [ 'posts' ] );
	} );
} );
