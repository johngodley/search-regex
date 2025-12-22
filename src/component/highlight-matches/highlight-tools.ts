type ReplacementType = 'delete' | 'replace' | 'match';

const replaceNonBreakingSpace = ( _match: string, spaces: string ): string => '\u00a0'.repeat( spaces.length );

/**
 * Replace invisible characters with an appropriate character
 *
 * @param text Text to use
 */
export function showInvisible( text: string ): string {
	return text
		.replace( /\n/g, '↵' )
		.replace( /^(\s+)/, replaceNonBreakingSpace )
		.replace( /(\s+)$/, replaceNonBreakingSpace );
}

/**
 * Perform a regular expression replace on a piece of text using a known set of captured values
 *
 * @param phrase   The text to replace
 * @param captures Array of captured regular expression words
 */
export function regexReplace( phrase: string, captures: string[] ): string {
	// Try to dynamically update the regex uses the captures
	if ( phrase && captures.length > 0 ) {
		return (
			phrase
				// Replace the captures - any $N that is not preceeded by a \
				.replace( /(\\?\$|\\?\\)+(\d{1,2})/g, ( match: string, _escaped: string, number: string ): string => {
					if ( match.substring( 0, 2 ) === '\\$' ) {
						return match.replace( '\\', '' );
					}

					const captureIndex = parseInt( number, 10 );
					const capture = captures[ captureIndex - 1 ];
					return capture !== undefined ? capture : match;
				} )
		);
	}

	return phrase;
}

/**
 * Given a set of changed replacements, return the first word that matches our criteria.
 *
 * @param words Array of words to check, in order of preference
 */
export function getMatchReplacement( words: Array< string | undefined | null > ): string | undefined {
	// Get non-undefined words
	const matched = words.filter( ( item ): item is string => item !== undefined && item !== null );

	// Return first non-undefined word, or the original
	return matched[ 0 ];
}

/**
 * Return 'delete', 'replace', 'match', depending on what type of match we have
 *
 * @param replacement Replacement word
 * @param original    Original search phrase
 */
export function getTypeOfReplacement( replacement: string, original: string ): ReplacementType {
	if ( replacement === '' ) {
		return 'delete';
	}

	if ( replacement !== original ) {
		return 'replace';
	}

	return 'match';
}
