/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { getNewActionFromResult } from 'state/search/selector';
import getValueType from 'component/value-type';

/**
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Is the row disabled
 * @param {import('state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {import('state/search/type').ModifyColumn|null} props.replacement - Row replacement value
 * @param {import('state/search/type.js').ResultColumn} props.column - Result column
 * @param {import('state/search/type').Schema} props.schema - Source schema
 */
function ColumnLabel( props ) {
	const { column, schema, setReplacement, replacement, toggle, disabled, canEdit, source, context } = props;
	const { column_label } = column;
	const typedContext = context.type === 'keyvalue' ? context.value : context;
	const valueType = typedContext.value_type ? getValueType( typedContext.value_type ) : null;

	function enable() {
		if ( disabled || ! canEdit ) {
			return;
		}

		const newAction = getNewActionFromResult( column, schema, source );

		setReplacement( replacement === null ? newAction : null );
		toggle && toggle();
	}

	return (
		<div
			className={ classnames(
				'searchregex-match__column',
				'searchregex-match__column__' + context.type,
				disabled || ! canEdit ? 'searchregex-match__column__disabled' : null
			) }
			title={
				valueType
					? __( 'This column contains special formatting. Modifying it could break the format.' )
					: __( 'Click to replace column' )
			}
			onClick={ enable }
		>
			{ column_label }
			{ valueType && <div className="searchregex-match__column__type">{ valueType }</div> }
		</div>
	);
}

export default ColumnLabel;
