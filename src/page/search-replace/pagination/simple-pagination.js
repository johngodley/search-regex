/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Nav from './nav-button';
import { search } from '../../../state/search/action';

function SimplePagination( props ) {
	const { progress, onChangePage, isLoading, matchedRows, perPage, noTotal = false, total } = props;
	const { current, previous, next } = progress;
	const totalPages = Math.ceil( matchedRows / perPage );
	const currentPage = Math.ceil( current / perPage );
	const hasNext = next && currentPage < totalPages;

	return (
		<div className="tablenav-pages">
			{ noTotal && <div>&nbsp;</div> }
			{ !noTotal && (
				<div className="displaying-num">
					{ sprintf( __( 'Matched rows: %(matches)s out of %(total)s total.', 'redirection' ), {
						matches: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( matchedRows ),
						total: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( total ),
					} ) }{ ' ' }
				</div>
			) }

			<div className="pagination-links">
				<Nav
					title={ __( 'First page', 'search-regex' ) }
					button="«"
					className="first-page"
					enabled={ previous !== false && !isLoading }
					onClick={ () => onChangePage( 0 ) }
				/>
				<Nav
					title={ __( 'Prev page', 'search-regex' ) }
					button="‹"
					className="prev-page"
					enabled={ previous !== false && !isLoading }
					onClick={ () => onChangePage( previous ) }
				/>

				<span className="tablenav-paging-text">
					{ sprintf( __( 'Page %(current)s of %(total)s', 'redirection' ), {
						current: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( currentPage ),
						total: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( totalPages ),
					} ) }
				</span>

				<Nav
					title={ __( 'Next page', 'search-regex' ) }
					button="›"
					className="next-page"
					enabled={ hasNext && !isLoading }
					onClick={ () => onChangePage( next ) }
				/>
				<Nav
					title={ __( 'Last page', 'search-regex' ) }
					button="»"
					className="last-page"
					enabled={ hasNext && !isLoading }
					onClick={ () => onChangePage( ( totalPages - 1 ) * perPage ) }
				/>
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
