/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { getMatchedTags } from 'state/preset/selector';
import Tag from './tag';

/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */
/** @typedef {import('react').ReactElement} ReactElement */

/**
 * @callback TagCallback
 * @param {string} prefix - Tag prefix
 * @param {string} name - Tag name
 * @param {number} position - Tag position
 */

/**
 * @callback ChangeCallback
 * @param {string} value - Replaced value
 */

/**
 *
 * @param {object} props - Component props
 * @param {string} props.phrase - Phrase to replace with
 * @param {ChangeCallback} props.onChange
 * @param {string} [props.className] - Class name
 * @param {boolean} [props.disabled=false] - Is it disabled?
 * @param {PresetValue} props.preset - Preset
 * @returns {ReactElement[]}
 */
function TaggedPhrases( props ) {
	const { phrase, onChange, className, preset, disabled = false } = props;
	const tags = getMatchedTags( preset.tags, phrase );

	return tags.map( ( tag ) => (
		<tr className={ classnames( 'searchregex-preset__tag', className ) } key={ tag.name }>
			<th>{ tag.title }</th>
			<td>
				<Tag tag={ tag } phrase={ phrase } onChange={ onChange } disabled={ disabled } />
			</td>
		</tr>
	) );
}

export default TaggedPhrases;
