import { showInvisible } from './highlight-tools';

interface ReplacementPhraseProps {
	match: string | null;
	originalMatch: string;
}

/**
 * Show the matched phrase.
 * @param root0
 * @param root0.match
 * @param root0.originalMatch
 */
const ReplacementPhrase = ( { match, originalMatch }: ReplacementPhraseProps ): JSX.Element | string => {
	if ( match === null ) {
		return <s>{ originalMatch }</s>;
	}

	return showInvisible( String( match ) );
};

export default ReplacementPhrase;
