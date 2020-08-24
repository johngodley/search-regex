/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

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
		<input
			type="text"
			value={ value }
			name="searchPhrase"
			placeholder={ __( 'Enter search phrase' ) }
			onChange={ ( ev ) => onChange( ev.target.value ) }
			disabled={ disabled }
		/>
	);
}

export default Search;
