/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */

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
