/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { useSelector } from 'react-redux';

/**
 * Internal dependencies
 */

import { getSchema } from 'state/search/selector';
import { hasFilterTag } from 'state/preset/selector';
import { Badge } from 'wp-plugin-components';
import Filter from 'component/schema/filter';

/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('state/search/type.js').Filter} Filter */

/**
 * Filter row
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disabled
 * @param {Filter} props.filters - Enabled filters
 */
function Filters( { filters, disabled, onSetSearch, tags, presetFilters } ) {
	const schema = useSelector( ( state ) => state.search.schema );

	/**
	 * @param {Filter} filters - Enabled filters
	 */
	function updateFilters( filters ) {
		onSetSearch( { filters } );
	}

	function canShowBadge( current, type ) {
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
			<th>{ __( 'Filters' ) }</th>
			<td>
				<div className="searchregex-filters">
					{ filters.map( ( { type, items }, rowPosition ) => {
						const filterForSource = presetFilters.find( ( item ) => item.type === type );
						const visibleItems = items.filter( ( item, pos ) => {
							if ( filterForSource ) {
								const matchingItem = filterForSource.items[ pos ];

								return ! hasFilterTag( tags, matchingItem );
							}

							return true;
						} );

						if ( visibleItems.length === 0 ) {
							return null;
						}

						return (
							<React.Fragment key={ type + '_' + rowPosition }>
								<Filter
									type={ type }
									schema={ getSchema( schema, type ) }
									items={ visibleItems }
									disabled={ disabled }
									source={ type }
									onChange={ ( updatedFilters ) =>
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
									<Badge disabled={ disabled }>{ __( 'AND' ) }</Badge>
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
