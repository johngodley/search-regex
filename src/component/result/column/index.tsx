import { useState } from 'react';
import { _n, sprintf } from '@wordpress/i18n';
import { getReplacedColumn } from '../modify-column';
import RestrictedMatches from './restricted-matches';
import ContextItem from './context-item';
import ContextValue from './context-value';
import getValueType from '../../value-type';
import { Badge } from '@wp-plugin-components';
import type { ResultColumn as ResultColumnType, SchemaColumn } from '../../../types/search';

const MORE_CONTEXTS = 2;

interface ContextBadgeProps {
	context: any;
}

function ContextBadge( { context }: ContextBadgeProps ): JSX.Element | null {
	const type = getValueType( context.type === 'keyvalue' ? context.value.value_type : context.value_type );

	if ( context.type === 'string' || ! type ) {
		return null;
	}

	return <Badge>{ type }</Badge>;
}

interface ResultColumnProps {
	rowId: string | number;
	disabled: boolean;
	column: ResultColumnType;
	schema: SchemaColumn;
	source: string;
}

function ResultColumn( props: ResultColumnProps ): JSX.Element {
	const { rowId, source } = props;
	const schema = { ...props.schema, source };
	const [ replacement, setReplacement ] = useState< any >( null );
	const [ showMore, setShowMore ] = useState( false );
	const column = getReplacedColumn( props.column, replacement, schema );
	const { contexts, context_count: contextCount } = column;
	const visibleContexts =
		contexts.length === 0
			? [ { type: 'empty', context_id: 0 } ]
			: contexts.slice( 0, showMore ? contexts.length : MORE_CONTEXTS );
	const remainingCount = contexts.length - visibleContexts.length;

	function save( newValue: any, newLabel?: string ): void {
		setReplacement(
			newValue === null ? newValue : { ...replacement, ...newValue, ...( newLabel ? { label: newLabel } : {} ) }
		);
	}

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
						{
							/* translators: %s: number of results to show */ sprintf(
								_n( 'Show %s more', 'Show %s more', remainingCount, 'search-regex' ),
								new Intl.NumberFormat( ( window as any ).SearchRegexi10n.locale as string ).format(
									remainingCount
								)
							)
						}
					</button>
				</p>
			) }
			{ showMore && contexts.length !== contextCount && <RestrictedMatches /> }
		</>
	);
}

export default ResultColumn;
