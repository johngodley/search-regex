/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import TextareaAutosize from 'react-textarea-autosize';

/**
 * Internal dependencies
 */

import TaggedPhrases from 'component/tagged-phrase';

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
 * @param {?PresetValue} props.preset
 * @param {OnChangeValue} props.onChange
 * @param {string} props.className
 * @returns {ReactElement}
 */
function Search( props ) {
	const { value, preset, onChange, disabled = false, className } = props;

	if ( preset ) {
		return (
			<TaggedPhrases
				disabled={ disabled }
				phrase={ preset.search.searchPhrase }
				onChange={ onChange }
				preset={ preset }
				className={ className }
				key={ preset.id }
			/>
		);
	}

	return (
		<TextareaAutosize
			value={ value }
			name="searchPhrase"
			cols={ 120 }
			maxRows={ 5 }
			onChange={ ( ev ) => onChange( ev.target.value ) }
			placeholder={ __( 'Enter search phrase' ) }
		/>
	);
}

export default Search;
