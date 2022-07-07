/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Select } from '@wp-plugin-components';

function getIntegerLogicOptions() {
	return [
		{
			value: 'set',
			label: __( 'Set Value' ),
		},
		{
			value: 'increment',
			label: __( 'Increment' ),
		},
		{
			value: 'decrement',
			label: __( 'Decrement' ),
		},
	];
}

function getStringLogicOptions() {
	return [
		{
			value: 'set',
			label: __( 'Set Value' ),
		},
		{
			value: 'replace',
			label: __( 'Replace' ),
		},
	];
}

function getMemberLogicOptions() {
	return [
		{
			value: 'replace',
			label: __( 'Replace With' ),
		},
		{
			value: 'include',
			label: __( 'Add' ),
		},
		{
			value: 'exclude',
			label: __( 'Remove' ),
		},
	];
}

function getKeyValueLogicOptions() {
	return [
		{
			value: 'add',
			label: __( 'Add' ),
		},
		{
			value: 'remove',
			label: __( 'Remove' ),
		},
	];
}

function getOperation( type ) {
	if ( type === 'member' ) {
		return getMemberLogicOptions();
	}

	if ( type === 'string' ) {
		return getStringLogicOptions();
	}

	if ( type === 'keyvalue' ) {
		return getKeyValueLogicOptions();
	}

	return getIntegerLogicOptions();
}

export default function Operation( { value, disabled, onChange, type, extraItems = [] } ) {
	return (
		<Select
			name="operation"
			items={ getOperation( type ).concat( extraItems ) }
			disabled={ disabled }
			value={ value }
			onChange={ ( ev ) => onChange( ev.target.value ) }
		/>
	);
}
