import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Select } from '@wp-plugin-components';
import type { SelectOption } from '../../../types/search';

function getIntegerLogicOptions(): SelectOption[] {
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

function getIntegerLogicJoin(): SelectOption[] {
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

function getStringLogicOptions(): SelectOption[] {
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

function getMemberLogicOptions(): SelectOption[] {
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

function getKeyValueLogicOptions(): SelectOption[] {
	return [
		{
			value: 'any',
			label: __( 'Any', 'search-regex' ),
		},
		...getStringLogicOptions(),
	];
}

function getLogic( type: 'string' | 'member' | 'integer' | 'keyvalue' | 'integer-join' ): SelectOption[] {
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
		return [ ...getIntegerLogicOptions(), ...getIntegerLogicJoin() ];
	}

	return getIntegerLogicOptions();
}

interface LogicProps {
	value: string;
	disabled: boolean;
	onChange: ( value: string ) => void;
	type: 'string' | 'member' | 'integer' | 'keyvalue' | 'integer-join';
}

export default function Logic( { value, disabled, onChange, type }: LogicProps ): JSX.Element {
	return (
		<Select
			name="logic"
			items={ getLogic( type ) }
			disabled={ disabled }
			value={ value }
			onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => onChange( ev.target.value ) }
		/>
	);
}
