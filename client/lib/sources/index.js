/**
 * The source name for custom post types
 */
export const SOURCE_TYPE_POSTS = 'posttype';

/**
 * The source name for all custom post types
 */
const SOURCE_ALL_POSTS = 'posts';

/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */

/**
 * Return all the custom post type names from the available sources
 * @param {Object} sources - All available sources
 * @returns {string[]} Array of custom post type names
 */
export function getAllPostTypes( sources ) {
	const postTypes = sources.find( ( source ) => source.name === SOURCE_TYPE_POSTS );

	if ( postTypes ) {
		return postTypes.sources.map( ( source ) => source.name );
	}

	return [];
}

/**
 * Remove the custom post types from an array of source names
 * @param {string[]} source - Array of source names
 * @param {SearchSourceGroup[]} sources - All available sources
 */
export function removePostTypes( source, sources ) {
	if ( source.indexOf( SOURCE_ALL_POSTS ) !== -1 ) {
		const allPostTypes = getAllPostTypes( sources );

		return source.filter( ( item ) => allPostTypes.indexOf( item ) === -1 );
	}

	return source;
}

/**
 * Get a particular source from the list of sources
 *
 * @param {SearchSourceGroup[]} sources - All available sources
 * @param {*} sourceName - Name of source to get
 */
export function getSource( sources, sourceName ) {
	for ( let index = 0; index < sources.length; index++ ) {
		for ( let subIndex = 0; subIndex < sources[ index ].sources.length; subIndex++ ) {
			if ( sources[ index ].sources[ subIndex ].name === sourceName ) {
				return sources[ index ].sources[ subIndex ];
			}
		}
	}

	return null;
}
