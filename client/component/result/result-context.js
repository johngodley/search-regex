/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-library/lib/locale';

/**
 * Internal dependencies
 */

import HighlightMatches from 'component/highlight-matches';

/**
 * @param {object} props - Component props
 * @param {number} props.rowId - Row ID
 * @param {boolean} props.isReplacing - Is replacing
 * @param {string} props.sourceType - Type of source
 * @param {string} props.contextReplacement - Phrase for this context
 */
function ResultContext( { item, rowId, contextReplacement, isReplacing, column, sourceType } ) {
	const { context, match_count, matches } = item;
	const { column_id, column_label } = column;

	return (
		<div className="searchregex-match">
			<div className="searchregex-match__column" title={ column_id }>{ column_label }</div>

			<HighlightMatches
				source={ context }
				matches={ matches }
				count={ match_count }
				sourceType={ sourceType }
				contextReplacement={ contextReplacement }
				columnId={ column_id }
				rowId={ rowId }
				isReplacing={ isReplacing }
			/>
		</div>
	);
}

export default ResultContext;
