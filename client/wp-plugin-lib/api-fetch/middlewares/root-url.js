/**
 * Internal dependencies
 */

function modifyQuery( root, url ) {
	if ( root.indexOf( '?' ) !== -1 ) {
		return url.replace( /\?/, '&' );
	}

	return url;
}

function createRootURLMiddleware( rootURL ) {
	function middleware( options, next ) {
		if ( options.url.substr( 0, 4 ) === 'http' ) {
			return next( options );
		}

		return next( {
			...options,
			url: ( rootURL.replace( /\/$/, '' ) + '/' + modifyQuery( rootURL, options.url ).replace( /^\//, '' ) ).replace(
				'wp-json/wp-json',
				'wp-json'
			),
		} );
	}

	middleware.rootURL = rootURL;

	return middleware;
}

export default createRootURLMiddleware;
