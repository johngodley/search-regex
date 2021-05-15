/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { Select } from 'wp-plugin-components';

/** @typedef {import('wp-plugin-components/select').SelectOption} SelectOption */

/**
 * @returns {SelectOption[]}
 */
function getIntegerLogicOptions() {
	return [
		{
			value: 'equals',
			label: __( 'Equals' ),
		},
		{
			value: 'notequals',
			label: __( 'Not Equals' ),
		},
		{
			value: 'greater',
			label: __( 'Greater' ),
		},
		{
			value: 'less',
			label: __( 'Less' ),
		},
		{
			value: 'range',
			label: __( 'Range' ),
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
			label: __( 'Has Owner' ),
		},
		{
			value: 'hasnot',
			label: __( 'No Owner' ),
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
			label: __( 'Equals' ),
		},
		{
			value: 'notequals',
			label: __( 'Not Equals' ),
		},
		{
			value: 'contains',
			label: __( 'Contains' ),
		},
		{
			value: 'notcontains',
			label: __( 'Not contains' ),
		},
		{
			value: 'begins',
			label: __( 'Begins' ),
		},
		{
			value: 'ends',
			label: __( 'End' ),
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
			label: __( 'Includes any' ),
		},
		{
			value: 'exclude',
			label: __( 'Excludes any' ),
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
			label: __( 'Any' ),
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
