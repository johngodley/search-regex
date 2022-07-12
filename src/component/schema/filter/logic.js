/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Select } from '@wp-plugin-components';

/** @typedef {import('wp-plugin-components/select').SelectOption} SelectOption */

/**
 * @returns {SelectOption[]}
 */
function getIntegerLogicOptions() {
	return [
		{
			value: 'equals',
			label: __( 'Equals', 'search-regex' ),
		},
		{
			value: 'notequals',
			label: __( 'Not Equals', 'search-regex' ),
		},
		{
			value: 'greater',
			label: __( 'Greater', 'search-regex' ),
		},
		{
			value: 'less',
			label: __( 'Less', 'search-regex' ),
		},
		{
			value: 'range',
			label: __( 'Range', 'search-regex' ),
		},
	];
}

/**
 * @returns {SelectOption[]}
 */
function getIntegerLogicJoin() {
	return [
		{
			value: 'has',
			label: __( 'Has Owner', 'search-regex' ),
		},
		{
			value: 'hasnot',
			label: __( 'No Owner', 'search-regex' ),
		},
	];
}

/**
 * @returns {SelectOption[]}
 */
function getStringLogicOptions() {
	return [
		{
			value: 'equals',
			label: __( 'Equals', 'search-regex' ),
		},
		{
			value: 'notequals',
			label: __( 'Not Equals', 'search-regex' ),
		},
		{
			value: 'contains',
			label: __( 'Contains', 'search-regex' ),
		},
		{
			value: 'notcontains',
			label: __( 'Not contains', 'search-regex' ),
		},
		{
			value: 'begins',
			label: __( 'Begins', 'search-regex' ),
		},
		{
			value: 'ends',
			label: __( 'End', 'search-regex' ),
		},
	];
}

/**
 * @returns {SelectOption[]}
 */
function getMemberLogicOptions() {
	return [
		{
			value: 'include',
			label: __( 'Includes any', 'search-regex' ),
		},
		{
			value: 'exclude',
			label: __( 'Excludes any', 'search-regex' ),
		},
	];
}

/**
 * @returns {SelectOption[]}
 */
function getKeyValueLogicOptions() {
	return [
		{
			value: 'any',
			label: __( 'Any', 'search-regex' ),
		},
	].concat( getStringLogicOptions() );
}

/**
 * @param {'string'|'member'|'integer'|'keyvalue'|'integer-join'} type - Type of logic
 * @returns {SelectOption[]}
 */
function getLogic( type ) {
	if ( type === 'member' ) {
		return getMemberLogicOptions();
	}

	if ( type === 'string' ) {
		return getStringLogicOptions();
	}

	if ( type === 'keyvalue' ) {
		return getKeyValueLogicOptions();
	}

	if ( type === 'integer-join' ) {
		return getIntegerLogicOptions().concat( getIntegerLogicJoin() );
	}

	return getIntegerLogicOptions();
}

/**
 * Filter logic
 * @param {object} props - Component props
 * @param {string} props.value - Logic value
 * @param {boolean} props.disabled - Disabled
 * @param {} props.onChange - Change callback
 * @param {'string'|'member'|'integer'|'keyvalue'} props.type - Type of logic
 */
export default function Logic( { value, disabled, onChange, type } ) {
	return (
		<Select
			name="logic"
			items={ getLogic( type ) }
			disabled={ disabled }
			value={ value }
			onChange={ ( ev ) => onChange( ev.target.value ) }
		/>
	);
}
