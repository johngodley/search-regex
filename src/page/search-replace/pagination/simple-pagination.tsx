import { __, sprintf } from '@wordpress/i18n';
import Nav from './nav-button';
import {
	useSearchStore,
	convertToSearchTotals,
	convertToSearchProgress,
	convertToResults,
} from '../../../stores/search-store';
import { useSearch } from '../../../hooks/use-search';
import { SEARCH_FORWARD, STATUS_FAILED, STATUS_COMPLETE, STATUS_IN_PROGRESS } from '../../../lib/constants';

interface Progress {
	current: number;
	previous: number | false;
	next: number | false;
}

interface SimplePaginationProps {
	progress: Progress;
	isLoading: boolean;
	matchedRows: number;
	perPage: number;
	noTotal?: boolean;
	total: number;
}

function SimplePagination( props: SimplePaginationProps ) {
	const searchValues = useSearchStore( ( state ) => state.search );
	const searchDirection = useSearchStore( ( state ) => state.searchDirection );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const setShowLoading = useSearchStore( ( state ) => state.setShowLoading );
	const setCanCancel = useSearchStore( ( state ) => state.setCanCancel );
	const setResultsDirty = useSearchStore( ( state ) => state.setResultsDirty );

	const searchMutation = useSearch();
	const { progress, isLoading, matchedRows, perPage, noTotal = false, total } = props;
	const { current = 0, previous, next } = progress;
	const totalPages = Math.ceil( matchedRows / perPage );
	// current represents the starting row index (0-based)
	// So current: 0 = page 1, current: 25 = page 2, current: 50 = page 3
	// Formula: page number = (current / perPage) + 1, but we need to handle 0 correctly
	const currentPage = current === 0 ? 0 : Math.floor( current / perPage );
	const hasNext = next !== false && currentPage < totalPages;

	function onChangePage( page: number ) {
		setResults( [] );
		setResultsDirty( false );
		setShowLoading( true );
		setCanCancel( true );
		setStatus( STATUS_IN_PROGRESS );

		searchMutation.mutate(
			{
				...searchValues,
				page,
				searchDirection: searchDirection || SEARCH_FORWARD,
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
					{
						/* translators: matches=number of matched rows, total=total number of rows */
						sprintf( __( 'Matched rows: %(matches)s out of %(total)s total.', 'search-regex' ), {
							matches: new Intl.NumberFormat( SearchRegexi10n.locale ).format( matchedRows ),
							total: new Intl.NumberFormat( SearchRegexi10n.locale ).format( total ),
						} )
					}{ ' ' }
				</div>
			) }

			<div className="pagination-links">
				<Nav
					title={ __( 'First page', 'search-regex' ) }
					button="«"
					className="first-page"
					enabled={ previous !== false && ! isLoading }
					onClick={ () => onChangePage( 0 ) }
				/>
				<Nav
					title={ __( 'Prev page', 'search-regex' ) }
					button="‹"
					className="prev-page"
					enabled={ previous !== false && ! isLoading }
					onClick={ () => onChangePage( previous as number ) }
				/>

				<span className="tablenav-paging-text">
					{ sprintf(
						/* translators: current=current page, total=total number of pages */
						__( 'Page %(current)s of %(total)s', 'search-regex' ),
						{
							current: new Intl.NumberFormat( SearchRegexi10n.locale ).format( currentPage + 1 ),
							total: new Intl.NumberFormat( SearchRegexi10n.locale ).format( totalPages ),
						}
					) }
				</span>

				<Nav
					title={ __( 'Next page', 'search-regex' ) }
					button="›"
					className="next-page"
					enabled={ hasNext && ! isLoading }
					onClick={ () => onChangePage( next as number ) }
				/>
				<Nav
					title={ __( 'Last page', 'search-regex' ) }
					button="»"
					className="last-page"
					enabled={ hasNext && ! isLoading }
					onClick={ () => onChangePage( ( totalPages - 1 ) * perPage ) }
				/>
			</div>
		</div>
	);
}

export default SimplePagination;
