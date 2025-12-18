import React, { useEffect, useState, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Select } from '@wp-plugin-components';
import type { PresetValue } from '../../types/preset';
import type { ResultColumn, SchemaColumn } from '../../types/search';
import type { SetReplace } from '../../state/search/type';

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
	const { disabled, replacement, setReplace } = props;
	const [ searchFlags, setFlags ] = useState( 'single' );
	const value = {
		id: 'replace',
		value: replacement ?? '',
		disabled: disabled || searchFlags === 'remove',
		placeholder:
			searchFlags === 'remove'
				? __( 'Matched values will be removed', 'search-regex' )
				: __( 'Enter replacement value', 'search-regex' ),
		name: 'replace',
		onChange: ( ev: ChangeEvent< HTMLInputElement | HTMLTextAreaElement > ) => {
			setReplace( { replacement: ev.target.value } );
		},
	};

	useEffect( () => {
		if ( searchFlags === 'remove' ) {
			setReplace( { replacement: null } );
		} else if ( replacement === null ) {
			setReplace( { replacement: '' } );
		}
	}, [ searchFlags, replacement, setReplace ] );

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
