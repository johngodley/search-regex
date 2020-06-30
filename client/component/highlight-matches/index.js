/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import RestrictedMatches from 'component/result/restricted-matches';
import Replacement from './replacement';
import { replaceRow } from 'state/search/action';
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
 * @param {boolean} props.isReplacing - Is replacing
 * @param {string} props.sourceType - Type of source
 * @param {Match} props.match - Match
 * @param {ReplaceCallback} props.onReplace - Perform a replacement
 * @param {SpecificCallback} props.setSpecific - Set the current replacement value
 * @param {string} props.contextReplacement - Global replacement
 */
function Match( props ) {
	const {
		beforePhrase,
		onReplace,
		sourceType,
		rowId,
		columnId,
		isReplacing,
		contextReplacement,
	} = props;
	const { context_offset, match, pos_id, captures, replacement } = props.match;

	return (
		<>
			{ beforePhrase }

			<Replacement
				onSave={ ( phrase ) => onReplace( phrase, sourceType, rowId, columnId, pos_id ) }
				isReplacing={ isReplacing }
				match={ match }
				captures={ captures }
				replacement={ [ contextReplacement, replacement ] }
				key={ context_offset }
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
 * @param {string} props.sourceType - Type of source
 * @param {string} props.contextReplacement - Phrase for this context
 * @param {number} props.count - Number of matches.
 * @param {ReplaceCallback} props.onReplace - Replace callback
 */
function HighlightMatches( props ) {
	const { matches, count, contextReplacement, onReplace, isReplacing, sourceType, source, columnId, rowId } = props;
	let offset = 0;

	return (
		<div className="searchregex-match__context">
			{ matches.map( ( match ) => {
				const oldOffset = offset;

				offset = match.context_offset + match.match.length;

				return (
					<Match
						match={ match }
						key={ match.pos_id }
						sourceType={ sourceType }
						columnId={ columnId }
						rowId={ rowId }
						onReplace={ onReplace }
						isReplacing={ isReplacing }
						beforePhrase={ source.substring( oldOffset, match.context_offset ) }
						contextReplacement={ contextReplacement }
					/>
				);
			} ) }

			{ source.substring( offset ) }

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
		onReplace: ( replacedPhrase, source, rowId, columnId, pos_id ) => {
			dispatch( replaceRow( replacedPhrase, source, rowId, columnId, pos_id ) );
		},
	};
}

export default connect(
	null,
	mapDispatchToProps
)( HighlightMatches );
