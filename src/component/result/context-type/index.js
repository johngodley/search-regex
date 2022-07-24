/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { Badge } from '@wp-plugin-components';
import HighlightMatches from '../../highlight-matches';

/** @typedef {import('../column/index.js').SetReplace} SetReplace */

const MAX_CONTEXT_LENGTH = 500;

function getValue( label, value ) {
	if ( parseInt( value, 10 ) > 0 ) {
		return sprintf(
			__( '%(label)s (ID %(id)d)', 'search-regex' ), {
				label,
				id: parseInt( value, 10 ),
			}
		);
	}

	return label;
}

/**
 * @param {object} props - Component props
 * @param {import('../state/search/type').ContextType} props.context - Context
 */
function ContextType( props ) {
	const { context, rowId, column, schema, setReplacement, className } = props;
	const { type } = context;
	const value = getValue( context.value_label || context.context || '', context.value );
	const replacement = getValue( context.replacement_label || '', context.replacement );

	if ( type === 'replace' ) {
		const isTooLong = value.length > 100 || replacement.length > 100;

		return (
			<div
				className={ classnames(
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
					className={ classnames( 'dashicons', {
						'dashicons-arrow-right-alt': !isTooLong,
						'dashicons-arrow-down-alt': isTooLong,
						'searchregex-list-replace__break': isTooLong,
					} ) }
				/>

				{ replacement.length === 0 ? (
					<span className={ classnames( 'searchregex-list__value', 'searchregex-list__novalue', className ) }>
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
			<span className={ classnames( 'searchregex-list__value', 'searchregex-list__novalue', className ) }>
				{ __( 'No value', 'search-regex' ) }
			</span>
		);
	}

	if ( type === 'value' ) {
		return (
			<div className={ classnames( className ) }>
				{ value.substring( 0, MAX_CONTEXT_LENGTH ) }
				{ value.length > MAX_CONTEXT_LENGTH && <span>...</span> }
			</div>
		);
	}

	if ( type === 'string' ) {
		return (
			<HighlightMatches
				source={ context.context }
				crop={ context.crop }
				matches={ context.matches }
				count={ context.match_count }
				setReplacement={ setReplacement }
				rowId={ rowId }
				column={ column }
				schema={ schema }
				className={ className }
			/>
		);
	}

	return (
		<Badge className={ classnames( 'searchregex-list__' + type, className ) }>
			{ value }
		</Badge>
	);
}

export default ContextType;
