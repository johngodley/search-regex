interface Source {
	label: string;
	name: string;
}

interface SearchSourceGroup {
	label: string;
	name: string;
	sources: Source[];
}

interface MultiOptionGroupValue {
	label: string;
	value: string;
	options: Array< {
		label: string;
		value: string;
	} >;
}

export function convertToSource( selected: string[] ): string[] {
	if ( selected.length === 0 ) {
		return [ 'posts' ];
	}

	return selected;
}

export function getSourcesForDropdown( sources: SearchSourceGroup[] ): MultiOptionGroupValue[] {
	return sources.map( ( sourceGroup ) => {
		return {
			label: sourceGroup.label,
			value: sourceGroup.name,
			options: sourceGroup.sources.map( ( { label, name } ) => {
				return {
					label,
					value: name,
				};
			} ),
		};
	} );
}
