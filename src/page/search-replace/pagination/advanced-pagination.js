/**
 * External dependencies
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import Nav from './nav-button';
import { SEARCH_FORWARD } from '../../../state/search/type';
import { search } from '../../../state/search/action';

const forwardPercent = ( total, current ) => current === false ? 100 : ( current / total ) * 100;
const backPercent = ( total, current ) => current === 0 ? current : ( current / total ) * 100;

export default function AdvancedPagination( props ) {
	const dispatch = useDispatch();
	const { total, progress, isLoading, searchDirection, noTotal = false, totals } = props;
	const { previous = false, next = false } = progress;

	function onChangePage( page, searchDirection ) {
		dispatch( search( page, searchDirection ) );
	}

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
						__( 'matched rows = %(searched)s', 'search-regex' ), {
						searched: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( totals.matched_rows ),
					} ) }
				</div>
			) }
			<div className="pagination-links">
				<Nav title={ __( 'First page', 'search-regex' ) } button="«" className="first-page" enabled={ previous && !isLoading } onClick={ () => onChangePage( 0, SEARCH_FORWARD ) } />

				<span className="tablenav-paging-text">
					{ sprintf(
						/* translators: %current: current percent progress */
						__( 'Progress %(current)s%%', 'search-regex' ), {
						current: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( searchDirection === SEARCH_FORWARD ? forwardPercent( total, next ).toFixed( 1 ) : backPercent( total, next === false ? previous : next ).toFixed( 1 ) ),
					}
					) }
				</span>

				<Nav title={ __( 'Next page', 'search-regex' ) } button="›" className="next-page" enabled={ next !== false && !isLoading } onClick={ () => onChangePage( next, SEARCH_FORWARD ) } />
			</div>
		</div>
	);
}
