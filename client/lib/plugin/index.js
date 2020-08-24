import { getPageUrl } from 'wp-plugin-lib/wordpress-url';

/**
 * Get the 'sub' parameter
 *
 * @param {string} url
 */
export function getPluginPage( url = '' ) {
	const params = getPageUrl( url );

	if ( params.sub && ALLOWED_PAGES.indexOf( params.sub ) !== -1 ) {
		return params.sub;
	}

	return ALLOWED_PAGES[ 0 ];
}

const ALLOWED_PAGES = [ 'search', 'options', 'support', 'presets' ];
