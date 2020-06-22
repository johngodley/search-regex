/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */

/**
 * Get preset by ID
 *
 * @param {PresetValue[]} presets - List of presets
 * @param {string} id - Preset ID
 * @returns {PresetValue|null}
 */
export function getPreset( presets, id ) {
	return presets.find( ( preset ) => preset.id === id );
}

/**
 * Is the search field locked?
 *
 * @param {string[]} locked - Array of locked fields
 * @param {string} field - Field to check
 * @returns {boolean}
 */
export function isLocked( locked, field ) {
	return locked && locked.indexOf( field ) !== -1;
}

/**
 * Does this phrase have any tags?
 *
 * @param {PresetTag[]} tags
 * @param {string} phrase
 * @returns {boolean}
 */
export function hasTags( tags, phrase ) {
	for ( let index = 0; index < tags.length; index++ ) {
		if ( phrase.indexOf( tags[ index ].name ) !== -1 ) {
			return true;
		}
	}

	return false;
}

/**
 * Does this phrase have any tags?
 *
 * @param {PresetTag[]} tags
 * @param {string} phrase
 * @returns {PresetTag[]} tags
 */
export function getMatchedTags( tags, phrase ) {
	return tags.filter( ( tag ) => hasTags( [ tag ], phrase ) );
}

/**
 * Get longest tag length
 *
 * @param {PresetTag[]} tags
 * @returns {number}
 */
export function getLongestTag( tags ) {
	let longest = 0;

	for ( let index = 0; index < tags.length; index++ ) {
		longest = Math.max( longest, tags[ index ].title.length );
	}

	return longest;
}

const HEADER_SHORT = 12;
const HEADER_MEDIUM = 30;

/**
 * Get tags as header values
 *
 * @param {PresetTag[]} tags
 * @returns {Object.<string,boolean>}
 */
export function getHeaderClass( tags ) {
	const longestTag = getLongestTag( tags );
	return {
		'searchregex-search__tag__short': longestTag < HEADER_SHORT,
		'searchregex-search__tag__medium': longestTag >= HEADER_SHORT && longestTag < HEADER_MEDIUM,
		'searchregex-search__tag__long': longestTag >= HEADER_MEDIUM,
	};
}

/**
 * @param {Object.<string,TagValue>} values - Tag values
 * @param {string} prefix - Tag prefix
 * @param {string} tagName - Tag name
 * @param {number} position - Tag position
 */
export function getTagValue( values, prefix, tagName, position ) {
	return values[ `${ prefix }-${ tagName }-${ position }` ] ?? '';
}
