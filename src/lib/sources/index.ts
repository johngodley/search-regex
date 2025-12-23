import type { SearchSource, SearchSourceGroup } from '../../types/search';

export function getSource( sources: SearchSourceGroup[], sourceName: string ): SearchSource | null {
	for ( let index = 0; index < sources.length; index++ ) {
		const sourceGroup = sources[ index ];
		if ( ! sourceGroup || ! sourceGroup.sources ) {
			continue;
		}

		for ( let subIndex = 0; subIndex < sourceGroup.sources.length; subIndex++ ) {
			const source = sourceGroup.sources[ subIndex ];
			if ( source && source.name === sourceName ) {
				return source;
			}
		}
	}

	return null;
}
