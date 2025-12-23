import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Select } from '@wp-plugin-components';
import type { SelectOption } from '../../../types/search';

function getIntegerLogicOptions(): SelectOption[] {
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

function getStringLogicOptions(): SelectOption[] {
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

function getMemberLogicOptions(): SelectOption[] {
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

function getKeyValueLogicOptions(): SelectOption[] {
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

function getOperation( type: string ): SelectOption[] {
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

interface OperationProps {
	value: string;
	disabled: boolean;
	onChange: ( value: string ) => void;
	type: string;
	extraItems?: SelectOption[];
}

export default function Operation( { value, disabled, onChange, type, extraItems = [] }: OperationProps ): JSX.Element {
	return (
		<Select
			name="operation"
			items={ [ ...getOperation( type ), ...extraItems ] }
			disabled={ disabled }
			value={ value }
			onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => onChange( ev.target.value ) }
		/>
	);
}
