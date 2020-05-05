/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __, numberFormat } from 'lib/locale';

/**
 * Internal dependencies
 */

import RestrictedMatches from './restricted-matches';
import ResultContext from './result-context';

const MORE_CONTEXTS = 2;

function ResultColumns( props ) {
	const { replacement, rowId, isReplacing, column } = props;
	const { contexts, context_count, match_count } = column;
	const [ showMore, setShowMore ] = useState( false );
	const visibleContexts = contexts.slice( 0, showMore ? contexts.length : MORE_CONTEXTS );
	const remainingCount = match_count - visibleContexts.reduce( ( accumulator, currentValue ) => {
		return accumulator + currentValue.match_count;
	}, 0 );

	return (
		<>
			{ visibleContexts.map( ( context ) => (
				<ResultContext
					item={ context }
					key={ rowId + '-' + context.context_id }
					column={ column }
					rowId={ rowId }
					contextReplacement={ replacement }
					isReplacing={ isReplacing }
				/>
			) ) }

			{ ! showMore && contexts.length > MORE_CONTEXTS && (
				<p>
					<button className="button button-secondary" onClick={ () => setShowMore( true ) }>
						{ __( 'Show %s more', 'Show %s more', {
							count: remainingCount,
							args: numberFormat( remainingCount ),
						} ) }
					</button>
				</p>
			) }
			{ showMore && contexts.length !== context_count && <RestrictedMatches /> }
		</>
	);
}

export default ResultColumns;
