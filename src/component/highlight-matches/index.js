/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import RestrictedMatches from '../result/column/restricted-matches';
import Replacement from './replacement';
import { saveRow } from '../../state/search/action';
import './style.scss';

/**
 * @typedef Match
 * @type
 * @property {number} context_offset - Offset in this context
 * @property {string} match - Matched phrase
 * @property {number} pos_id - Position ID
 * @property {string} replacement - Replacement string
 * @property {string[]} captures - Captured data
 */

/**
 * @callback ReplaceCallback
 * @param {string} replacedPhrase
 * @param {string} source
 * @param {number} rowId
 * @param {string} columnId
 * @param {number} pos_id
 */

/**
 * @callback SpecificCallback
 * @param {string} specific - Replacement
 */

/**
 * Display a matched phrase.
 *
 * @param {object} props - Component props
 * @param {string} props.beforePhrase - Text before the match
 * @param {string} props.afterPhrase - Text after the match
 * @param {string} props.matchedPhrase - Matched word
 * @param {number} props.rowId - Row ID
 * @param {string} props.columnId - Column ID
 * @param {string} props.sourceType - Type of source
 * @param {Match} props.match - Match
 * @param {ReplaceCallback} props.onReplace - Perform a replacement
 * @param {SpecificCallback} props.setSpecific - Set the current replacement value
 */
function Match( props ) {
	const { beforePhrase, onReplace, column, schema, setReplacement, rowId } = props;
	const { match, pos_id, captures, replacement } = props.match;

	return (
		<>
			{ beforePhrase }

			<Replacement
				onSave={ ( phrase ) => onReplace( phrase, match, rowId, pos_id ) }
				match={ match }
				replacement={ replacement }
				captures={ captures }
				canReplace={ true }
				setReplacement={ setReplacement }
				column={ column }
				schema={ schema }
				rowId={ rowId }
			/>
		</>
	);
}

/**
 * Highlight all matches in a context.
 *
 * @param {object} props - Component props
 * @param {Match[]} props.matches
 * @param {string} props.source - Source string.
 * @param {number} props.rowId - Row ID
 * @param {string} props.columnId - Column ID
 * @param {boolean} props.isReplacing - Is replacing
 * @param {string} props.contextReplacement - Phrase for this context
 * @param {number} props.count - Number of matches.
 * @param {ReplaceCallback} props.onReplace - Replace callback
 */
function HighlightMatches( props ) {
	const { matches, count, onReplace, source, column, schema, className, rowId, crop = [] } = props;
	let offset = 0;

	return (
		<div className={ classnames( 'searchregex-match__context', className ) }>
			{ matches.map( ( match, pos ) => {
				const oldOffset = offset;

				offset = match.context_offset + match.match.length;

				return (
					<Match
						match={ match }
						key={ match.pos_id }
						onReplace={ onReplace }
						beforePhrase={
							<>
								{ crop.start > 0 && pos === 0 && <>&hellip; </> }
								{ source.substring( oldOffset, match.context_offset ) }
							</>
						}
						column={ column }
						schema={ schema }
						rowId={ rowId }
					/>
				);
			} ) }

			{ source.substring( offset ) }
			{ crop.end > 0 && <> &hellip;</> }

			{ matches.length !== count && <RestrictedMatches /> }
		</div>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		/**
		 * @param {string} replacedPhrase
		 * @param {string} source
		 * @param {number} rowId
		 * @param {string} columnId
		 * @param {number} pos_id
		 */
		onReplace: ( replaceValue, searchValue, rowId, posId ) => {
			dispatch(
				saveRow(
					{
						...replaceValue,
						searchValue,
						posId,
					},
					rowId
				)
			);
		},
	};
}

export default connect(
	null,
	mapDispatchToProps
)( HighlightMatches );
