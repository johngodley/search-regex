/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import TextareaAutosize from 'react-textarea-autosize';

/** @typedef {import('react').ReactElement} ReactElement */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

/**
 * @callback OnChangeValue
 * @param {string} value
 */

/**
 *
 * @param {object} props
 * @param {boolean} [props.disabled=false] - Is this disabled?
 * @param {string} props.value
 * @param {OnChangeValue} props.onChange
 * @param {string} props.className
 * @param {boolean} [props.multiline=false]
 * @returns {ReactElement}
 */
function Search( props ) {
	const { value, onChange, disabled = false, multiline = false } = props;

	if ( multiline ) {
		return (
			<TextareaAutosize
				value={ value }
				name="searchPhrase"
				cols={ 120 }
				minRows={ 2 }
				maxRows={ 5 }
				onChange={ ( ev ) => onChange( ev.target.value ) }
				disabled={ disabled }
				placeholder={ __( 'Enter search phrase' ) }
			/>
		);
	}

	return (
		<input
			value={ value }
			type="text"
			name="searchPhrase"
			disabled={ disabled }
			onChange={ ( ev ) => onChange( ev.target.value ) }
			placeholder={ __( 'Optional global search phrase. Leave blank to use filters only.' ) }
		/>
	);
}

export default Search;
