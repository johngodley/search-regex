import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Badge } from '@wp-plugin-components';
import HighlightMatches from '../../highlight-matches';
import type { ResultColumn, SchemaColumn, Schema, Match } from '../../../types/search';

const MAX_CONTEXT_LENGTH = 500;

function getValue( label: string ): string {
	return label;
}

interface ContextTypeContext {
	type: string;
	value_label?: string;
	context?: string;
	value: string;
	replacement_label?: string;
	replacement?: string;
	crop?: any;
	matches?: Match[];
	match_count?: number;
}

interface ContextTypeProps {
	context: ContextTypeContext;
	rowId: string | number;
	column: ResultColumn;
	schema: SchemaColumn;
	setReplacement: ( value: any ) => void;
	className?: string;
}

function ContextType( props: ContextTypeProps ): JSX.Element {
	const { context, rowId, column, schema, setReplacement, className } = props;
	void setReplacement;
	// setReplacement is currently unused but kept in props for API consistency.
	void props.setReplacement;
	const { type } = context;
	const value = getValue( context.value_label || context.context || '' );
	const replacement = getValue( context.replacement_label || '' );
	if ( type === 'replace' ) {
		const isTooLong = value.length > 100 || replacement.length > 100;

		return (
			<div
				className={ clsx(
					'searchregex-list-replace',
					isTooLong && 'searchregex-list-replace__vertical',
					className
				) }
			>
				<Badge className={ 'searchregex-list__delete' }>
					{ value.substring( 0, MAX_CONTEXT_LENGTH ) || __( 'No value', 'search-regex' ) }
					{ value.length > MAX_CONTEXT_LENGTH && <span>...</span> }
				</Badge>

				<span
					className={ clsx( 'dashicons', {
						'dashicons-arrow-right-alt': ! isTooLong,
						'dashicons-arrow-down-alt': isTooLong,
						'searchregex-list-replace__break': isTooLong,
					} ) }
				/>

				{ replacement.length === 0 ? (
					<span className={ clsx( 'searchregex-list__value', 'searchregex-list__novalue', className ) }>
						{ __( 'Empty value', 'search-regex' ) }
					</span>
				) : (
					<Badge className={ 'searchregex-list__add' }>
						{ replacement.substring( 0, MAX_CONTEXT_LENGTH ) }
						{ replacement.length > MAX_CONTEXT_LENGTH && <span>...</span> }
					</Badge>
				) }
			</div>
		);
	}

	if ( type === 'empty' || ( type === 'value' && value.length === 0 ) ) {
		return (
			<span className={ clsx( 'searchregex-list__value', 'searchregex-list__novalue', className ) }>
				{ __( 'No value', 'search-regex' ) }
			</span>
		);
	}

	if ( type === 'value' ) {
		return (
			<div className={ clsx( className ) }>
				{ value.substring( 0, MAX_CONTEXT_LENGTH ) }
				{ value.length > MAX_CONTEXT_LENGTH && <span>...</span> }
			</div>
		);
	}

	if ( type === 'string' ) {
		return (
			<HighlightMatches
				source={ context.context || '' }
				crop={ context.crop }
				matches={ context.matches || [] }
				count={ context.match_count || 0 }
				rowId={ Number( rowId ) }
				column={ column.column_id }
				schema={ { name: '', type: '', columns: [ schema ] } as Schema }
				{ ...( className !== undefined ? { className } : {} ) }
			/>
		);
	}

	return <Badge className={ clsx( 'searchregex-list__' + type, className ) }>{ value }</Badge>;
}

export default ContextType;
