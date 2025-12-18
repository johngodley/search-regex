import React from 'react';
import { __ } from '@wordpress/i18n';
import { useSelector } from 'react-redux';
import { getSchema } from '../../../state/search/selector';
import { hasFilterTag } from '../../../state/preset/selector';
import { Badge } from '@wp-plugin-components';
import Filter from '../../../component/schema/filter';
import type { PresetTag } from '../../../types/preset';
import type { Schema, FilterItem } from '../../../types/search';

interface FilterGroup {
	type: string;
	items: FilterItem[];
}

interface RootState {
	search: {
		schema: Schema[];
	};
}

interface FiltersProps {
	filters: FilterGroup[];
	disabled: boolean;
	onSetSearch: ( values: { filters: FilterGroup[] } ) => void;
	tags: PresetTag[];
	presetFilters: FilterGroup[];
}

function Filters( { filters, disabled, onSetSearch, tags, presetFilters }: FiltersProps ) {
	const schema = useSelector( ( state: RootState ) => state.search.schema );

	function updateFilters( updatedFilters: FilterGroup[] ) {
		onSetSearch( { filters: updatedFilters } );
	}

	function canShowBadge( current: number, type: string ): boolean {
		if ( current === filters.length - 1 ) {
			return false;
		}

		return filters[ current + 1 ].type === type;
	}

	if ( filters.length === 0 ) {
		return null;
	}

	return (
		<tr>
			<th>{ __( 'Filters', 'search-regex' ) }</th>
			<td>
				<div className="searchregex-filters">
					{ filters.map( ( { type, items }, rowPosition ) => {
						const filterForSource = presetFilters.find( ( filterItem ) => filterItem.type === type );
						const visibleItems = items.filter( ( _item, pos ) => {
							if ( filterForSource ) {
								const matchingItem = filterForSource.items[ pos ];

								return ! hasFilterTag( tags, matchingItem );
							}

							return true;
						} );

						if ( visibleItems.length === 0 ) {
							return null;
						}

						const sourceSchema = getSchema( schema, type );
						if ( ! sourceSchema ) {
							return null;
						}

						return (
							<React.Fragment key={ type + '_' + rowPosition }>
								<Filter
									schema={ sourceSchema }
									items={ visibleItems }
									disabled={ disabled }
									source={ type }
									onChange={ ( updatedFilters: FilterItem[] ) =>
										updateFilters( [
											...filters.slice( 0, rowPosition ),
											{
												...filters[ rowPosition ],
												items: updatedFilters,
											},
											...filters.slice( rowPosition + 1 ),
										] )
									}
									onRemove={ () =>
										updateFilters( [
											...filters.slice( 0, rowPosition ),
											...filters.slice( rowPosition + 1 ),
										] )
									}
								/>
								{ canShowBadge( rowPosition, type ) ? (
									<Badge disabled={ disabled }>{ __( 'AND', 'search-regex' ) }</Badge>
								) : (
									<div className="searchregex-filters__break" />
								) }
							</React.Fragment>
						);
					} ) }
				</div>
			</td>
		</tr>
	);
}

export default Filters;
