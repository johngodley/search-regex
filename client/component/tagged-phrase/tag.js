/**
 * External dependencies
 */

import React, { useState } from 'react';

/** @typedef {import('./index.js').ChangeCallback} ChangeCallback */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */
/** @typedef {import('react').SyntheticEvent} SyntheticEvent */

/**
 * Replace all occurences of the tag in the phrase
 *
 * @param {string} phrase
 * @param {string} tag
 * @param {string} value
 */
function replaceTags( phrase, tag, value ) {
	const name = tag.replace( /[.*+\-?^${}()|[\]\\]/g, '\\$&' );

	return phrase.replace( new RegExp( name, 'g' ), value );
}

/**
 * Tag
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disabled
 * @param {string} props.phrase
 * @param {ChangeCallback} props.onChange
 * @param {PresetTag} props.tag
 */
function Tag( props ) {
	const { tag, onChange, phrase, disabled } = props;
	const [ value, setValue ] = useState( '' );

	/**
	 * @param {SyntheticEvent} ev Event
	 */
	function updateTag( ev ) {
		setValue( ev.target.value );
		onChange( replaceTags( phrase, tag.name, ev.target.value ) );
	}

	return <input type="text" value={ value } placeholder={ tag.title } onChange={ updateTag } disabled={ disabled } />;
}

export default Tag;
