import React from 'react';
import { __ } from '@wordpress/i18n';
import { getSchema } from '../../../lib/search-utils';
import { hasFilterTag } from '../../../lib/preset-utils';
import { Badge } from '@wp-plugin-components';
import Filter from '../../../component/schema/filter';
import { useSearchStore } from '../../../stores/search-store';
import type { PresetTag } from '../../../types/preset';
import type { FilterItem } from '../../../types/search';

interface FilterGroup {
	type: string;
	items: FilterItem[];
}

function isFilterGroup( filter: FilterGroup | { items: FilterItem[]; type?: string } ): filter is FilterGroup {
	return typeof filter.type === 'string';
}

interface FiltersProps {
	filters: FilterGroup[];
	disabled: boolean;
	onSetSearch: ( values: { filters: FilterGroup[] } ) => void;
	tags: PresetTag[];
	presetFilters: FilterGroup[];
}

function Filters( { filters, disabled, onSetSearch, tags, presetFilters }: FiltersProps ) {
	const schema = useSearchStore( ( state ) => state.schema );

	function updateFilters( updatedFilters: Array< FilterGroup | { items: FilterItem[]; type?: string } > ) {
		const validFilters = updatedFilters.filter( isFilterGroup ) as FilterGroup[];
		onSetSearch( { filters: validFilters } );
	}

	function canShowBadge( current: number, type: string ): boolean {
		if ( current === filters.length - 1 ) {
			return false;
		}

		const nextFilter = filters[ current + 1 ];
		return nextFilter ? nextFilter.type === type : false;
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
								if ( ! matchingItem ) {
									return true;
								}

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
									onChange={ ( updatedFilters: FilterItem[] ) => {
										const currentFilter = filters[ rowPosition ];
										if ( ! currentFilter ) {
											return;
										}
										updateFilters( [
											...filters.slice( 0, rowPosition ),
											{
												type: currentFilter.type,
												items: updatedFilters,
											},
											...filters.slice( rowPosition + 1 ),
										] );
									} }
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
