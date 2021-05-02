/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import { hasValue } from '../modify-column';
import ColumnLabel from '../column-label';
import { Dropdown } from 'wp-plugin-components';
import ReplaceForm from 'component/replace-form';
import { saveRow } from 'state/search/action';
import getValueType from 'component/value-type';

function ContextItem( props ) {
	const { column, schema, replacement, save, disabled, source, rowId, children, context } = props;
	const canReplace = hasValue( replacement, column, schema );
	const dispatch = useDispatch();

	function onSave( toggle ) {
		// Remove the modal
		toggle();

		// Cancel any local modifications
		save( null );

		// Save the changes
		dispatch( saveRow( replacement, rowId ) );
	}

	return (
		<div className="searchregex-match searchregex-match__list">
			<Dropdown
				renderToggle={ ( isOpen, toggle ) => (
					<ColumnLabel
						column={ column }
						schema={ schema }
						replacement={ replacement }
						setReplacement={ save }
						disabled={ disabled }
						source={ source }
						toggle={ toggle }
						canEdit={ schema.modify !== false }
						context={ context }
					/>
				) }
				hasArrow
				align="centre"
				valign="top"
				onClose={ () => save( null ) }
				renderContent={ ( toggle ) => (
					<ReplaceForm
						setReplacement={ save }
						replacement={ replacement }
						context={ context }
						canReplace={ canReplace }
						rowId={ rowId }
						onSave={ () => onSave( toggle ) }
						onCancel={ () => {
							save( null );
							toggle();
						} }
						column={ column }
						schema={ schema }
						source={ source }
						className="searchregex-replace__modal"
						description={ getValueType(
							context.type === 'keyvalue' ? context.value.value_type : context.value_type
						) ? __( 'Contains encoded data' ) : '' }
					/>
				) }
			/>

			{ children }
		</div>
	);
}

export default ContextItem;
