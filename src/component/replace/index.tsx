import React, { useEffect, useState, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Select } from '@wp-plugin-components';
import type { PresetValue } from '../../types/preset';
import type { ResultColumn, SchemaColumn, SetReplace } from '../../types/search';

interface ReplaceProps {
	disabled: boolean;
	className?: string;
	placeholder?: string | React.ReactElement;
	setReplace: SetReplace;
	replacement: string | null;
	preset?: PresetValue | null;
	schema: SchemaColumn;
	column: ResultColumn;
}

function Replace( props: ReplaceProps ): JSX.Element {
	const { disabled, replacement, setReplace, placeholder } = props;
	const [ searchFlags, setFlags ] = useState( 'single' );

	// Ensure placeholder is always a string for input/textarea
	let placeholderText: string;
	if ( searchFlags === 'remove' ) {
		placeholderText = __( 'Matched values will be removed', 'search-regex' );
	} else if ( typeof placeholder === 'string' ) {
		placeholderText = placeholder;
	} else {
		placeholderText = __( 'Enter replacement value', 'search-regex' );
	}

	const value = {
		id: 'replace',
		value: replacement ?? '',
		disabled: disabled || searchFlags === 'remove',
		placeholder: placeholderText,
		name: 'replace',
		onChange: ( ev: ChangeEvent< HTMLInputElement | HTMLTextAreaElement > ) => {
			setReplace( { replacement: ev.target.value } );
		},
	};

	useEffect( () => {
		if ( searchFlags === 'remove' && replacement !== null ) {
			setReplace( { replacement: null } );
		} else if ( searchFlags !== 'remove' && replacement === null ) {
			setReplace( { replacement: '' } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ searchFlags ] );

	return (
		<>
			{ searchFlags.indexOf( 'multi' ) === -1 ? <input type="text" { ...value } /> : <textarea { ...value } /> }

			<Select
				disabled={ disabled }
				items={ [
					{ value: '', label: __( 'Single', 'search-regex' ) },
					{ value: 'multi', label: __( 'Multi', 'search-regex' ) },
					{ value: 'remove', label: __( 'Remove', 'search-regex' ) },
				] }
				value={ searchFlags }
				name="search-flags"
				onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => setFlags( ev.target.value ) }
			/>
		</>
	);
}

export default Replace;
