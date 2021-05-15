/** @typedef {import('state/search/type').SearchSourceGroup} SearchSourceGroup */

/**
 * If 'all post types' is enabled, then disable all individual post types.
 *
 * If a specific post type is then disabled, enable all the other post types and disable 'all post types'.
 *
 * Default to 'post' and 'page' if no sources are picked
 *
 * @param {string[]} selected - Array of selected options
 */
export function convertToSource( selected ) {
	if ( selected.length === 0 ) {
		return [ 'posts' ];
	}

	return selected;
}

/**
 * Convert the sources object into a format suitable for the MultiGroupDropdown
 *
 * @param {SearchSourceGroup[]} sources - Array of SearchSourceGroup objects
 * @returns {MultiOptionGroupValue[]}
 */
export function getSourcesForDropdown( sources ) {
	return sources.map( ( sourceGroup ) => {
		return {
			label: sourceGroup.label,
			value: sourceGroup.name,
			options: sourceGroup.sources.map( ( { label, name } ) => {
				return {
					label,
					value: name,
				};
			} ),
		};
	} );
}
