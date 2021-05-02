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

function replaceSearchTags( search, tag, value ) {
	return {
		...search,
		searchPhrase: replaceTags( search.searchPhrase, tag, value ),
		replacement: replaceTags( search.replacement, tag, value ),
		filters: search.filters.map( ( filter ) => ( {
			...filter,
			items: filter.items.map( ( item ) => ( {
				...item,
				...( item.value === undefined
					? {}
					: {
							value: replaceTags( item.value, tag, value ),
					  } ),
			} ) ),
		} ) ),
		actionOption: Array.isArray( search.actionOption )
			? search.actionOption.map( ( item ) =>
					item.searchValue === undefined
						? item
						: {
								...item,
								searchValue: replaceTags( item.searchValue, tag, value ),
								replaceValue: replaceTags( item.replaceValue, tag, value ),
						  }
			  )
			: search.actionOption,
	};
}

/**
 * Tag
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disabled
 * @param {ChangeCallback} props.onChange
 * @param {PresetTag} props.tag
 */
function Tag( props ) {
	const { tag, onChange, search, disabled } = props;
	const [ value, setValue ] = useState( '' );

	/**
	 * @param {SyntheticEvent} ev Event
	 */
	function updateTag( ev ) {
		setValue( ev.target.value );

		onChange( replaceSearchTags( search, tag.name, ev.target.value ) );
	}

	return <input type="text" value={ value } placeholder={ tag.title } onChange={ updateTag } disabled={ disabled } />;
}

export default Tag;
