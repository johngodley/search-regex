/**
 * External dependencies
 */

import React from 'react';
import { translate as __, numberFormat } from 'wp-plugin-library/lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Nav from './nav-button';
import { search } from 'state/search/action';

function SimplePagination( props ) {
	const { progress, onChangePage, isLoading, matchedPhrases, matchedRows, perPage, noTotal = false } = props;
	const { current, previous, next } = progress;
	const totalPages = Math.ceil( matchedRows / perPage );
	const currentPage = Math.ceil( current / perPage ) + 1;
	const hasNext = next && currentPage < totalPages;

	return (
		<div className="tablenav-pages">
			{ noTotal && <div>&nbsp;</div> }
			{ ! noTotal && <div className="displaying-num">
				{ __( 'Matches: %(phrases)s across %(rows)s database row.', 'Matches: %(phrases)s across %(rows)s database rows.', {
						count: matchedRows,
						args: {
							phrases: numberFormat( matchedPhrases ),
							rows: numberFormat( matchedRows ),
						},
					} ) }
			</div> }

			<div className="pagination-links">
				<Nav title={ __( 'First page' ) } button="«" className="first-page" enabled={ previous !== false && ! isLoading } onClick={ () => onChangePage( 0 ) } />
				<Nav title={ __( 'Prev page' ) } button="‹" className="prev-page" enabled={ previous !== false && ! isLoading } onClick={ () => onChangePage( previous ) } />

				<span className="tablenav-paging-text">
					{ __( 'Page %(current)s of %(total)s', {
						args: {
							current: numberFormat( currentPage ),
							total: numberFormat( totalPages ),
						},
					} ) }
				</span>

				<Nav title={ __( 'Next page' ) } button="›" className="next-page" enabled={ hasNext && ! isLoading } onClick={ () => onChangePage( next ) } />
				<Nav title={ __( 'Last page' ) } button="»" className="last-page" enabled={ hasNext && ! isLoading } onClick={ () => onChangePage( ( totalPages - 1 ) * perPage ) } />
			</div>
		</div>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onChangePage: ( page ) => {
			dispatch( search( page ) );
		},
	};
}

export default connect(
	null,
	mapDispatchToProps
)( SimplePagination );
