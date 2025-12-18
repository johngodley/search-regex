import { __, _n, sprintf } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';
import Nav from './nav-button';
import { SEARCH_FORWARD } from '../../../state/search/type';
import { search } from '../../../state/search/action';

interface Progress {
	previous?: number | false;
	next?: number | false;
}

interface Totals {
	matched_rows: number; // eslint-disable-line camelcase
}

interface AdvancedPaginationProps {
	total: number;
	progress: Progress;
	isLoading: boolean;
	searchDirection: string;
	noTotal?: boolean;
	totals: Totals;
}

const forwardPercent = ( total: number, current: number | false ): number =>
	current === false ? 100 : ( current / total ) * 100;
const backPercent = ( total: number, current: number | false ): number =>
	current === false || current === 0 ? 0 : ( current / total ) * 100;

export default function AdvancedPagination( props: AdvancedPaginationProps ) {
	const dispatch = useDispatch();
	const { total, progress, isLoading, searchDirection, noTotal = false, totals } = props;
	const { previous = false, next = false } = progress;

	function onChangePage( page: number, direction: string ) {
		/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
		dispatch( search( page, direction ) as any );
		/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
	}

	return (
		<div className="tablenav-pages">
			{ noTotal && <div>&nbsp;</div> }
			{ ! noTotal && (
				<div className="displaying-num">
					{ sprintf(
						/* translators: %s: total number of rows searched */
						_n( '%s database row in total', '%s database rows in total', total, 'search-regex' ),
						new Intl.NumberFormat( window.SearchRegexi10n.locale ).format( total )
					) }
					&nbsp;&mdash;&nbsp;
					{
						/* eslint-disable camelcase */
						sprintf(
							/* translators: %(searched)s: number of rows matched */
							__( 'matched rows = %(searched)s', 'search-regex' ),
							{
								searched: new Intl.NumberFormat( window.SearchRegexi10n.locale ).format(
									totals.matched_rows
								),
							}
						)
						/* eslint-enable camelcase */
					}
				</div>
			) }
			<div className="pagination-links">
				<Nav
					title={ __( 'First page', 'search-regex' ) }
					button="«"
					className="first-page"
					enabled={ previous !== false && ! isLoading }
					onClick={ () => onChangePage( 0, SEARCH_FORWARD ) }
				/>

				<span className="tablenav-paging-text">
					{ sprintf(
						/* translators: %current: current percent progress */
						__( 'Progress %(current)s%%', 'search-regex' ),
						{
							current:
								searchDirection === SEARCH_FORWARD
									? forwardPercent( total, next ).toFixed( 1 )
									: backPercent( total, next === false ? previous : next ).toFixed( 1 ),
						}
					) }
				</span>

				<Nav
					title={ __( 'Next page', 'search-regex' ) }
					button="›"
					className="next-page"
					enabled={ next !== false && ! isLoading }
					onClick={ () => onChangePage( next as number, SEARCH_FORWARD ) }
				/>
			</div>
		</div>
	);
}
