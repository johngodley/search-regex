/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import ModifyInteger from '../../modify/types/integer';

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('state/search/type').SchemaColumn} props.schema
 * @param {import('state/search/type').ResultColumn} props.column
 * @param {import('state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 **/
function ReplaceInteger( props ) {
	const { schema, replacement, disabled, setReplacement, fetchData } = props;

	return (
		<ModifyInteger
			schema={ schema }
			disabled={ disabled }
			item={ replacement }
			fixOperation="set"
			onChange={ setReplacement }
			fetchData={ fetchData }
		/>
	);
}

export default ReplaceInteger;
