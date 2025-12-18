import type { PresetTag } from '../../types/preset';

interface FilterItem {
	value?: string;
	[ key: string ]: unknown;
}

interface Filter {
	items: FilterItem[];
	[ key: string ]: unknown;
}

interface ActionOption {
	searchValue?: string;
	replaceValue?: string;
	[ key: string ]: unknown;
}

interface SearchValues {
	searchPhrase: string;
	replacement: string;
	filters: Filter[];
	actionOption: ActionOption[] | unknown;
}

/**
 * Replace all occurences of the tag in the phrase
 * @param phrase
 * @param tag
 * @param value
 */
function replaceTag( phrase: string, tag: string, value: string ): string {
	const name = tag.replace( /[.*+\-?^${}()|[\]\\]/g, '\\$&' );

	return phrase.replace( new RegExp( name, 'g' ), value );
}

function replaceTags( phrase: string, tags: PresetTag[], tagValues: string[] ): string {
	return tags.reduce( ( prev, current, index ) => replaceTag( prev, current.name, tagValues[ index ] ), phrase );
}

export default function replaceSearchTags(
	search: SearchValues,
	tags: PresetTag[],
	tagValues: string[]
): Partial< SearchValues > {
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
