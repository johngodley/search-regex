/**
 * Internal dependencies
 */

import { showInvisible } from './highlight-tools';

/**
 * Show the matched phrase.
 * @param {object} props - Component props
 * @param {string} props.match - The current matched phrase
 * @param {string} props.originalMatch - The original matched phrase
 * @returns {object|string}
 */
const ReplacementPhrase = ( { match, originalMatch } ) => {
	if ( match === null ) {
		return (
			<strike>{ originalMatch }</strike>
		);
	}

	return showInvisible( String( match ) );
}

export default ReplacementPhrase;
