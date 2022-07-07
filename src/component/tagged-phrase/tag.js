/**
 * Replace all occurences of the tag in the phrase
 *
 * @param {string} phrase
 * @param {string} tag
 * @param {string} value
 */
function replaceTag( phrase, tag, value ) {
	const name = tag.replace( /[.*+\-?^${}()|[\]\\]/g, '\\$&' );

	return phrase.replace( new RegExp( name, 'g' ), value );
}

function replaceTags( phrase, tags, tagValues ) {
	return tags.reduce( ( prev, current, index ) => replaceTag( prev, current.name, tagValues[ index ] ), phrase );
}

export default function replaceSearchTags( search, tags, tagValues ) {
	return {
		searchPhrase: replaceTags( search.searchPhrase, tags, tagValues ),
		replacement: replaceTags( search.replacement, tags, tagValues ),
		filters: search.filters.map( ( filter ) => ( {
			...filter,
			items: filter.items.map( ( item ) => ( {
				...item,
				...( item.value === undefined
					? {}
					: {
							value: replaceTags( item.value, tags, tagValues ),
					  } ),
			} ) ),
		} ) ),
		actionOption: Array.isArray( search.actionOption )
			? search.actionOption.map( ( item ) =>
					item.searchValue === undefined
						? item
						: {
								...item,
								searchValue: replaceTags( item.searchValue, tags, tagValues ),
								replaceValue: replaceTags( item.replaceValue, tags, tagValues ),
						  }
			  )
			: search.actionOption,
	};
}
