const replaceNonBreakingSpace = ( match, spaces ) => '\u00a0'.replace( spaces.length );

/**
 * Replace invisible characters with an appropriate character
 * @param {string} text Text to use
 */
export function showInvisible( text ) {
	return text
		.replace( /\n/g, '↵' )
		.replace( /^(\s+)/, replaceNonBreakingSpace )
		.replace( /(\s+)$/, replaceNonBreakingSpace );
}

/**
 * Perform a regular expression replace on a piece of text using a known set of captured values
 * @param {string} phrase The text to replace
 * @param {Array} captures Array of captured regular expression words
 */
export function regexReplace( phrase, captures ) {
	// Try to dynamically update the regex uses the captures
	if ( phrase && captures.length > 0 ) {
		return phrase
			// Replace the captures - any $N that is not preceeded by a \
			.replace( /(\\?)\$(\d{1,2})/g, ( match, escaped, number ) => {
				number = parseInt( number, 10 );
				if ( escaped.length > 0 ) {
					return match.replace( '\\', '' );
				}

				return captures[ number - 1 ] !== undefined ? captures[ number -1 ] : match;
			} )
	}

	return phrase;
}

const hasChanged = ( value, originalPhrase ) => value === null || ( value !== originalPhrase && value !== '' );

/**
 * Given a set of changed replacements, return the first word that matches our criteria.
 * @param {Array} words Array of words to check, in order of preference
 * @param {string} original Original word to use as fallback
 */
export function getMatchReplacement( words, original ) {
	const matched = words.find( ( item ) => hasChanged( item, original ) );

	return matched === undefined ? original : matched;
}

/**
 * Return 'delete', 'replace', 'match', depending on what type of match we have
 * @param {string} replacement Replacement word
 * @param {string} original Original search phrase
 */
export function getTypeOfReplacement( replacement, original ) {
	if ( replacement === null ) {
		return 'delete';
	}

	if ( replacement !== original ) {
		return 'replace';
	}

	return 'match';
}
