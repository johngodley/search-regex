/**
 * External dependencies
 */

import { useState } from 'react';
import { __, numberFormat } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getReplacedColumn } from '../modify-column';
import RestrictedMatches from './restricted-matches';
import ContextItem from './context-item';
import ContextValue from './context-value';
import getValueType from '../../value-type';
import { Badge } from '@wp-plugin-components';

const MORE_CONTEXTS = 2;

function ContextBadge( { context } ) {
	const type = getValueType( context.type === 'keyvalue' ? context.value.value_type : context.value_type );

	if ( context.type === 'string' || ! type ) {
		return null;
	}

	return <Badge>{ type }</Badge>;
}

/**
 * @param {object} props - Component props
 * @param {number} props.rowId - Row ID
 * @param {boolean} props.disabled - Is the row disabled
 * @param {import('../state/search/type.js').ResultColumn} props.column - Result column
 * @param {import('../state/search/type').Schema} props.schema - Source schema
 */
function ResultColumn( props ) {
	const { rowId, source } = props;
	const schema = { ...props.schema, source };
	const [ replacement, setReplacement ] = useState( null );
	const [ showMore, setShowMore ] = useState( false );
	const column = getReplacedColumn( props.column, replacement, schema );
	const { contexts, context_count } = column;
	const visibleContexts =
		contexts.length === 0
			? [ { type: 'empty', context_id: 0 } ]
			: contexts.slice( 0, showMore ? contexts.length : MORE_CONTEXTS );
	const remainingCount = contexts.length - visibleContexts.length;

	function save( newValue, newLabel ) {
		setReplacement(
			newValue === null ? newValue : { ...replacement, ...newValue, ...( newLabel ? { label: newLabel } : {} ) }
		);
	}

	// One context - display it inline
	if ( visibleContexts.length === 1 ) {
		return (
			<ContextItem replacement={ replacement } save={ save } { ...props } context={ visibleContexts[ 0 ] }>
				<ContextValue
					rowId={ rowId }
					column={ column }
					schema={ schema }
					setReplacement={ setReplacement }
					context={ visibleContexts[ 0 ] }
				/>
			</ContextItem>
		);
	}

	// Multiple contexts? Display the label, then the rest in a list
	return (
		<>
			<ContextItem replacement={ replacement } save={ save } { ...props } context={ visibleContexts[ 0 ] } />

			<ul className="searchregex-match__contexts">
				{ visibleContexts.map( ( context ) => (
					<li key={ context.context_id }>
						<ContextBadge context={ context } />

						<ContextValue
							rowId={ rowId }
							column={ column }
							schema={ schema }
							setReplacement={ setReplacement }
							context={ context }
							key={ context.context_id }
						/>
					</li>
				) ) }
			</ul>

			{ ! showMore && contexts.length > MORE_CONTEXTS && (
				<p>
					<button className="button button-secondary" onClick={ () => setShowMore( true ) } type="button">
						{ __( 'Show %s more', 'Show %s more', {
							count: remainingCount,
							args: numberFormat( remainingCount, 0 ),
						} ) }
					</button>
				</p>
			) }
			{ showMore && contexts.length !== context_count && <RestrictedMatches /> }
		</>
	);
}

export default ResultColumn;
