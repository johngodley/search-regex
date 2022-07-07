/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import ModifyMember from '../../modify/types/member';

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('../state/search/type').SchemaColumn} props.schema
 * @param {import('../state/search/type').ResultColumn} props.column
 * @param {import('../state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 **/
function ReplaceMember( props ) {
	const { schema, replacement, disabled, setReplacement, fetchData, column } = props;

	return (
		<ModifyMember
			schema={ schema }
			disabled={ disabled }
			item={ replacement }
			fixOperation="set"
			onChange={ setReplacement }
			fetchData={ fetchData }
			localLabels={ column.contexts.map( ( item ) => ( { value: column.column_id + '_' + item.value, label: item.value_label } ) ) }
		/>
	);
}

export default ReplaceMember;
