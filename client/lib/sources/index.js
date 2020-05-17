/**
 * The source name for custom post types
 */
export const SOURCE_TYPE_POSTS = 'posttype';

/**
 * The source name for all custom post types
 */
const SOURCE_ALL_POSTS = 'posts';

/**
 * Return all the custom post type names from the available sources
 * @param {Object} sources All available sources
 * @returns {Array} Array of custom post type names
 */
export function getAllPostTypes( sources ) {
	const postTypes = sources.find( source => source.name === SOURCE_TYPE_POSTS );

	if ( postTypes ) {
		return postTypes.sources.map( source => source.name );
	}

	return [];
}

/**
 * Remove the custom post types from an array of source names
 * @param {Array} source Array of source names
 * @param {Object} sources All available sources
 */
export function removePostTypes( source, sources ) {
	if ( source.indexOf( SOURCE_ALL_POSTS ) !== -1 ) {
		const allPostTypes = getAllPostTypes( sources );

		return source.filter( item => allPostTypes.indexOf( item ) === -1 );
	}

	return source;
}
