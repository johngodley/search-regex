import { __, _n, sprintf } from '@wordpress/i18n';
import Nav from './nav-button';
import { SEARCH_FORWARD, STATUS_FAILED, STATUS_COMPLETE, STATUS_IN_PROGRESS } from '../../../lib/constants';
import {
	useSearchStore,
	convertToSearchTotals,
	convertToSearchProgress,
	convertToResults,
} from '../../../stores/search-store';
import { useSearch } from '../../../hooks/use-search';

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
	const searchValues = useSearchStore( ( state ) => state.search );
	const results = useSearchStore( ( state ) => state.results );
	const cumulativeMatchedRows = useSearchStore( ( state ) => state.cumulativeMatchedRows );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const setShowLoading = useSearchStore( ( state ) => state.setShowLoading );
	const setCanCancel = useSearchStore( ( state ) => state.setCanCancel );
	const setResultsDirty = useSearchStore( ( state ) => state.setResultsDirty );
	const setSearchDirection = useSearchStore( ( state ) => state.setSearchDirection );
	const addToCumulativeMatchedRows = useSearchStore( ( state ) => state.addToCumulativeMatchedRows );

	const searchMutation = useSearch();
	const { total, progress, isLoading, searchDirection, noTotal = false, totals } = props;
	const { previous = false, next = false } = progress;

	function onChangePage( page: number, direction: string ) {
		// Before clearing results, add current matches to cumulative total
		addToCumulativeMatchedRows( results.length );

		setResults( [] );
		setResultsDirty( false );
		setShowLoading( true );
		setCanCancel( true );
		setSearchDirection( direction );
		setStatus( STATUS_IN_PROGRESS );

		searchMutation.mutate(
			{
				...searchValues,
				page,
				searchDirection: direction,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Convert API results (number row_id) to Result[] (string row_id)
					setResults( convertToResults( data.results ) );
					setTotals( convertToSearchTotals( data.totals ) );
					setProgress( convertToSearchProgress( data.progress ) );
					setStatus( data.status ?? STATUS_COMPLETE );
					setShowLoading( false );
					setCanCancel( false );
				},
				onError: () => {
					setStatus( STATUS_FAILED );
					setShowLoading( false );
					setCanCancel( false );
				},
			}
		);
	}

	return (
		<div className="tablenav-pages">
			{ noTotal && <div>&nbsp;</div> }
			{ ! noTotal && (
				<div className="displaying-num">
					{ sprintf(
						/* translators: %s: total number of rows searched */
						_n( '%s database row in total', '%s database rows in total', total, 'search-regex' ),
						new Intl.NumberFormat( SearchRegexi10n.locale ).format( total )
					) }
					&nbsp;&mdash;&nbsp;
					{
						/* eslint-disable camelcase */
						sprintf(
							/* translators: %(searched)s: number of rows matched */
							__( 'matched rows = %(searched)s', 'search-regex' ),
							{
								searched: new Intl.NumberFormat( SearchRegexi10n.locale ).format( totals.matched_rows ),
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
					enabled={ cumulativeMatchedRows > 0 && previous !== false && ! isLoading }
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
