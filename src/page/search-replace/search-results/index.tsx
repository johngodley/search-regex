import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { connect } from 'react-redux';
import Result from '../../../component/result';
import TableLoading from './table-loading';
import EmptyResults from './empty-results';
import Pagination from '../pagination';
import { STATUS_IN_PROGRESS } from '../../../state/settings/type';
import { searchMore, setError } from '../../../state/search/action';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from '../../../state/search/type';
import { isAdvancedSearch } from '../../../state/search/selector';
import { useSlidingSearchWindow } from '../../../lib/result-window';
import type { Result as ResultType } from '../../../types/search';
import './style.scss';

interface Progress {
	next: number | false;
	previous: number | false;
}

interface Totals {
	matched_rows: number;
	rows: number;
}

interface SearchValues {
	perPage: number;
	[ key: string ]: unknown;
}

interface RootState {
	search: {
		results: ResultType[];
		status: string;
		progress: Progress;
		totals: Totals;
		requestCount: number;
		searchDirection: string;
		search: SearchValues;
		showLoading: boolean;
		resultsDirty: boolean;
	};
}

interface SearchResultsProps {
	results: ResultType[];
	totals: Totals;
	progress: Progress;
	status: string;
	requestCount: number;
	search: SearchValues;
	searchDirection: string;
	showLoading: boolean;
	resultsDirty: boolean;
	onError: () => void;
	onSearchMore: ( page: number | false, perPage: number, limit: number ) => void;
}

const hasMoreResults = ( searchDirection: string, progress: Progress ): boolean =>
	( searchDirection === SEARCH_FORWARD && progress.next !== false ) ||
	( searchDirection === SEARCH_BACKWARD && progress.previous !== false );
const shouldLoadMore = ( status: string, requestCount: number, results: ResultType[], perPage: number ): boolean =>
	status === STATUS_IN_PROGRESS && requestCount > 0 && results.length < perPage;

function SearchResults( props: SearchResultsProps ) {
	const {
		results,
		totals,
		progress,
		status,
		requestCount,
		search,
		searchDirection,
		showLoading,
		resultsDirty,
		onError,
	} = props;
	const { perPage } = search;
	const { onSearchMore } = props;
	const isAdvanced = isAdvancedSearch( search );
	const isLoading = status === STATUS_IN_PROGRESS;
	const canLoad =
		isAdvanced &&
		shouldLoadMore( status, requestCount, results, perPage ) &&
		hasMoreResults( searchDirection, progress );

	useSlidingSearchWindow(
		canLoad,
		requestCount,
		perPage,
		( size: number ) =>
			onSearchMore(
				searchDirection === SEARCH_FORWARD ? progress.next : progress.previous,
				size,
				perPage - results.length
			),
		onError
	);

	return (
		<>
			<Pagination
				totals={ totals }
				perPage={ perPage }
				isLoading={ isLoading }
				progress={ progress }
				searchDirection={ searchDirection }
				advanced={ isAdvanced }
				resultsDirty={ resultsDirty }
			/>

			<table
				className={ classnames(
					'wp-list-table',
					'widefat',
					'fixed',
					'striped',
					'items',
					'searchregex-results'
				) }
			>
				<thead>
					<tr>
						<th className="searchregex-result__table">{ __( 'Source', 'search-regex' ) }</th>
						<th className="searchregex-result__row">{ __( 'Row ID', 'search-regex' ) }</th>
						<th className="searchregex-result__match">{ __( 'Matched Content', 'search-regex' ) }</th>
					</tr>
				</thead>

				<tbody>
					{ results.map( ( result ) => (
						<Result key={ result.source_type + '-' + result.row_id } result={ result } />
					) ) }

					{ showLoading && <TableLoading columns={ 3 } /> }

					{ ! isLoading && results.length === 0 && <EmptyResults columns={ 3 } /> }
				</tbody>
			</table>

			<Pagination
				totals={ totals }
				perPage={ perPage }
				isLoading={ isLoading }
				progress={ progress }
				searchDirection={ searchDirection }
				noTotal
				advanced={ isAdvanced }
				resultsDirty={ resultsDirty }
			/>
		</>
	);
}

function mapStateToProps( state: RootState ) {
	const { results, status, progress, totals, requestCount, searchDirection, search, showLoading, resultsDirty } =
		state.search;

	return {
		results,
		status,
		progress,
		searchDirection,
		totals,
		requestCount,
		search,
		showLoading,
		resultsDirty,
	};
}

/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
function mapDispatchToProps( dispatch: any ) {
	return {
		onSearchMore: ( page: number | false, perPage: number, limit: number ) => {
			if ( page !== false ) {
				dispatch( searchMore( page, perPage, limit ) );
			}
		},
		onError: () => {
			dispatch(
				setError(
					__( 'Your search resulted in too many requests. Please narrow your search terms.', 'search-regex' )
				)
			);
		},
	};
}
/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */

export default connect( mapStateToProps, mapDispatchToProps )( SearchResults );
