/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import replaceSearchTags from './tag';

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
 * @param {object} props.search - Search
 * @param {ChangeCallback} props.onChange
 * @param {string} [props.className] - Class name
 * @param {boolean} [props.disabled=false] - Is it disabled?
 * @param {PresetValue} props.preset - Preset
 * @returns {ReactElement[]}
 */
function TaggedPhrases( props ) {
	const { search, values, onChange, className, tags, disabled = false } = props;
	const [ tagValues, setTagValues ] = useState( tags.map( ( item ) => '' ) );

	function updateTag( value, pos ) {
		const newValues = tagValues.slice( 0, pos ).concat( value, tagValues.slice( pos + 1 ) );

		setTagValues( newValues );
		onChange( { ...values, ...replaceSearchTags( search, tags, newValues ) } );
	}

	return tags.map( ( tag, pos ) => (
		<tr className={ classnames( 'searchregex-preset__tag', className ) } key={ tag.name }>
			<th>{ tag.title }</th>
			<td>
				<input
					type="text"
					value={ tagValues[ pos ] }
					placeholder={ tag.title }
					onChange={ ( ev ) => updateTag( ev.target.value, pos ) }
					disabled={ disabled }
				/>
			</td>
		</tr>
	) );
}

export default TaggedPhrases;
