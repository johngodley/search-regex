/** @typedef {import('../state/preset/type.js').PresetValue} PresetValue */
/** @typedef {import('../state/preset/type.js').PresetTag} PresetTag */

const HEADER_SHORT = 12;
const HEADER_MEDIUM = 30;

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
	if ( phrase ) {
		for ( let index = 0; index < tags.length; index++ ) {
			if ( phrase.indexOf( tags[ index ].name ) !== -1 ) {
				return true;
			}
		}
	}

	return false;
}

export function hasFilterTag( tags, filter ) {
	return filter.value && hasTags( tags, filter.value );
}

export function hasActionTag( tags, action ) {
	if ( action.searchValue !== undefined ) {
		return hasTags( tags, action.searchValue ) || hasTags( tags, action.replaceValue );
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
function getLongestTag( tags ) {
	let longest = 0;

	for ( let index = 0; index < tags.length; index++ ) {
		longest = Math.max( longest, tags[ index ].title.length );
	}

	return longest;
}

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

function applyTags( phrase, tags ) {
	return tags.reduce( ( prev, current ) => {
		return prev.replace( current.name, current.value );
	}, phrase );
}

/**
 * Get the default preset values for a preset with tags
 *
 * @param {PresetValue} preset
 * @returns {{searchPhrase: string, replacement: string}|null}
 */
export function getDefaultPresetValues( preset ) {
	if ( ! preset ) {
		return null;
	}

	const emptyTags = preset.tags.map( ( tag ) => ( { name: tag.name, value: '' } ) );

	return {
		...preset.search,
		searchPhrase: applyTags( preset.search.searchPhrase, emptyTags ),
		replacement: applyTags( preset.search.replacement, emptyTags ),
	};
}
