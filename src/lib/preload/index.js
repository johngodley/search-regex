/**
 * Return a preloaded data value
 * @param {String} name Preload name
 * @param {Any} defaultValue The value to return if `name` doesn't exist
 */
export default function getPreload( name, defaultValue ) {
	if ( typeof SearchRegexi10n !== 'undefined' && SearchRegexi10n.preload && SearchRegexi10n.preload[ name ] ) {
		return SearchRegexi10n.preload[ name ];
	}

	return defaultValue;
}
