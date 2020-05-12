/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';

/**
 * Internal dependencies
 */

import HighlightMatches from 'component/highlight-matches';

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
