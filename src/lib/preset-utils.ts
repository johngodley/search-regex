import type { PresetValue, PresetTag } from '../types/preset';

const HEADER_SHORT = 12;
const HEADER_MEDIUM = 30;

export function getPreset( presets: PresetValue[], id: string ): PresetValue | undefined {
	return presets.find( ( preset ) => preset.id === id );
}

export function isLocked( locked: string[], field: string ): boolean {
	return locked && locked.indexOf( field ) !== -1;
}

export function hasTags( tags: PresetTag[], phrase: string ): boolean {
	if ( phrase ) {
		for ( let index = 0; index < tags.length; index++ ) {
			const tag = tags[ index ];
			if ( tag && phrase.indexOf( tag.name ) !== -1 ) {
				return true;
			}
		}
	}

	return false;
}

export function hasFilterTag( tags: PresetTag[], filter: { value?: string } ): boolean {
	return filter?.value !== undefined && hasTags( tags, filter.value );
}

export function hasActionTag( tags: PresetTag[], action: { searchValue?: string; replaceValue?: string } ): boolean {
	if ( action.searchValue !== undefined ) {
		return (
			hasTags( tags, action.searchValue ) ||
			( action.replaceValue !== undefined && hasTags( tags, action.replaceValue ) )
		);
	}
	return false;
}

export function getMatchedTags( tags: PresetTag[], phrase: string ): PresetTag[] {
	return tags.filter( ( tag ) => hasTags( [ tag ], phrase ) );
}

function getLongestTag( tags: PresetTag[] ): number {
	let longest = 0;

	for ( let index = 0; index < tags.length; index++ ) {
		const tag = tags[ index ];
		if ( tag ) {
			longest = Math.max( longest, tag.label.length );
		}
	}

	return longest;
}

export function getHeaderClass( tags: PresetTag[] ): Record< string, boolean > {
	const longestTag = getLongestTag( tags );
	return {
		'searchregex-search__tag__short': longestTag < HEADER_SHORT,
		'searchregex-search__tag__medium': longestTag >= HEADER_SHORT && longestTag < HEADER_MEDIUM,
		'searchregex-search__tag__long': longestTag >= HEADER_MEDIUM,
	};
}

function applyTags( phrase: string, tags: Array< { name: string; value: string } > ): string {
	return tags.reduce( ( prev, current ) => {
		return prev.replace( current.name, current.value );
	}, phrase );
}

export function getDefaultPresetValues(
	preset: PresetValue | null
): { searchPhrase: string; replacement: string } | null {
	if ( ! preset ) {
		return null;
	}

	const emptyTags = preset.tags.map( ( tag ) => ( { name: tag.name, value: '' } ) );

	return {
		...preset.search,
		searchPhrase: applyTags( preset.search.searchPhrase ?? '', emptyTags ),
		replacement: applyTags( preset.search.replacement ?? '', emptyTags ),
	};
}
