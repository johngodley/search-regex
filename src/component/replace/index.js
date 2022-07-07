/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import './style.scss';
import { Select } from '@wp-plugin-components';

/** @typedef {import('../state/preset/type.js').PresetValue} PresetValue */

/**
 * @callback SaveCallback
 * @param {string} phrase
 */

/**
 * @callback CancelCallback
 */

/**
 * A replacement dialog
 *
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Whether we can replace this
 * @param {string} props.className - Class
 * @param {string|React} props.placeholder - Placeholder string
 * @param {import('../state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 * @param {?PresetValue} [props.preset]
 * @param {import('../state/search/type').SchemaColumn} props.schema
 * @param {import('../state/search/type').ResultColumn} props.column
 */
function Replace( props ) {
	const { disabled, replacement, setReplace } = props;
	const [ searchFlags, setFlags ] = useState( 'single' );
	const value = {
		id: 'replace',
		value: replacement,
		disabled: disabled || searchFlags === 'remove',
		placeholder: searchFlags === 'remove' ? __( 'Matched values will be removed' ) : __( 'Enter replacement value' ),
		name: 'replace',
		onChange: ( ev ) => {
			setReplace( { replacement: ev.target.value } );
		},
	};

	useEffect( () => {
		if ( searchFlags === 'remove' ) {
			setReplace( { replacement: null } );
		} else if ( replacement === null ) {
			setReplace( { replacement: '' } );
		}
	}, [ searchFlags ] );

	return (
		<>
			{ searchFlags.indexOf( 'multi' ) === -1 ? <input type="text" { ...value } /> : <textarea { ...value } /> }

			<Select
				disabled={ disabled }
				items={ [
					{ value: '', label: __( 'Single' ) },
					{ value: 'multi', label: __( 'Multi' ) },
					{ value: 'remove', label: __( 'Remove' ) },
				] }
				value={ searchFlags }
				name="search-flags"
				onChange={ ( ev ) => setFlags( ev.target.value ) }
			/>
		</>
	);
}

export default Replace;
