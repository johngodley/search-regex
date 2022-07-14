/**
 * External dependencies
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Nav from './nav-button';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from '../../../state/search/type';
import { search } from '../../../state/search/action';

const forwardPercent = ( total, current ) => current === false ? 100 : ( current / total ) * 100;
const backPercent = ( total, current ) => current === 0 ? current : ( current / total ) * 100;

function AdvancedPagination( props ) {
	const { total, progress, onChangePage, isLoading, searchDirection, noTotal = false, totals } = props;
	const { previous = false, next = false } = progress;

	return (
		<div className="tablenav-pages">
			{ noTotal && <div>&nbsp;</div> }
			{ !noTotal && (
				<div className="displaying-num">
					{ sprintf(
						/* translators: %s: total number of rows searched */
						_n( '%s database row in total', '%s database rows in total', total, 'search-regex' ),
						new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( total )
					) }

					&nbsp;&mdash;&nbsp;

					{ sprintf(
						/* translators: %searched: number of rows searched and matched %phrases: number of phrases matched */
						__( 'matched rows = %(searched)s, phrases = %(found)s', 'search-regex' ), {
						searched: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( totals.matched_rows ),
						found: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( totals.matched_phrases ),
					} ) }
				</div>
			) }
			<div className="pagination-links">
				<Nav title={ __( 'First page', 'search-regex' ) } button="«" className="first-page" enabled={ previous && !isLoading } onClick={ () => onChangePage( 0, SEARCH_FORWARD ) } />
				<Nav title={ __( 'Prev page', 'search-regex' ) } button="‹" className="prev-page" enabled={ previous && !isLoading } onClick={ () => onChangePage( previous, SEARCH_BACKWARD ) } />

				<span className="tablenav-paging-text">
					{ sprintf(
						/* translators: %current: current percent progress */
						__( 'Progress %(current)s%%', 'search-regex' ), {
							current: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( searchDirection === SEARCH_FORWARD ? forwardPercent( total, next ) : backPercent( total, next == false ? previous : next ) ),
						}
					) }
				</span>

				<Nav title={ __( 'Next page', 'search-regex' ) } button="›" className="next-page" enabled={ next !== false && !isLoading } onClick={ () => onChangePage( next, SEARCH_FORWARD ) } />
			</div>
		</div>
	);
}


function mapStateToProps( state ) {
	const { search } = state.search;

	return {
		search,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onChangePage: ( page, searchDirection ) => {
			dispatch( search( page, searchDirection ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( AdvancedPagination );
