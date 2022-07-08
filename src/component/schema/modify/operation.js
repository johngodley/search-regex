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
			label: __( 'Set Value', 'search-regex' ),
		},
		{
			value: 'increment',
			label: __( 'Increment', 'search-regex' ),
		},
		{
			value: 'decrement',
			label: __( 'Decrement', 'search-regex' ),
		},
	];
}

function getStringLogicOptions() {
	return [
		{
			value: 'set',
			label: __( 'Set Value', 'search-regex' ),
		},
		{
			value: 'replace',
			label: __( 'Replace', 'search-regex' ),
		},
	];
}

function getMemberLogicOptions() {
	return [
		{
			value: 'replace',
			label: __( 'Replace With', 'search-regex' ),
		},
		{
			value: 'include',
			label: __( 'Add', 'search-regex' ),
		},
		{
			value: 'exclude',
			label: __( 'Remove', 'search-regex' ),
		},
	];
}

function getKeyValueLogicOptions() {
	return [
		{
			value: 'add',
			label: __( 'Add', 'search-regex' ),
		},
		{
			value: 'remove',
			label: __( 'Remove', 'search-regex' ),
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
