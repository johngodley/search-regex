import type { SearchSource, SearchSourceGroup } from '../../types/search';

export function getSource( sources: SearchSourceGroup[], sourceName: string ): SearchSource | null {
	for ( let index = 0; index < sources.length; index++ ) {
		for ( let subIndex = 0; subIndex < sources[ index ].sources.length; subIndex++ ) {
			if ( sources[ index ].sources[ subIndex ].name === sourceName ) {
				return sources[ index ].sources[ subIndex ];
			}
		}
	}

	return null;
}
