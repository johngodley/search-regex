/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { getMatchedTags, getTagValue } from 'state/preset/selector';

/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */

/**
 * @callback TagCallback
 * @param {string} prefix - Tag prefix
 * @param {string} name - Tag name
 * @param {number} position - Tag position
 */

/**
 * @callback ChangeCallback
 * @param {string} prefix - Tag prefix
 * @param {string} tagName - Tag name
 * @param {number} position - Tag position
 * @param {string} tagValue - Tag value
 */

/**
 *
 * @param {object} props - Component props
 * @param {string} props.phrase - Phrase to replace with
 * @param {string} props.prefix - Prefix for the tag value
 * @param {PresetTag[]} props.tagValues - Tag values
 * @param {ChangeCallback} props.onChange
 * @param {string} props.className - Class name
 * @param {PresetTag[]} props.tags - Tags
 */
function TaggedPhrases( props ) {
	const { tags, phrase, onChange, prefix, tagValues, className, preset } = props;
	const matchedTags = getMatchedTags( tags, phrase );

	return matchedTags.map( ( tag, pos ) => (
		<tr className={ classnames( 'searchregex-preset__tag', className ) } key={ tag.name }>
			<th>{ tag.title }</th>
			<td>
				<input
					type="text"
					value={ getTagValue( tagValues, prefix, tag.name, pos ) }
					name={ prefix + '_' + tag.name + '_' + pos }
					placeholder={ tag.title }
					onChange={ ( ev ) => onChange( prefix, tag.name, pos, ev.target.value, preset ) }
				/>
			</td>
		</tr>
	) );
}

export default TaggedPhrases;
