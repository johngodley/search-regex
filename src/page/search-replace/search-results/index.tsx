import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import Result from '../../../component/result';
import TableLoading from './table-loading';
import EmptyResults from './empty-results';
import Pagination from '../pagination';
import {
	STATUS_IN_PROGRESS,
	STATUS_COMPLETE,
	SEARCH_FORWARD,
	SEARCH_BACKWARD,
	STATUS_FAILED,
} from '../../../lib/constants';
import { isAdvancedSearch } from '../../../lib/search-utils';
import { useSlidingSearchWindow } from '../../../lib/result-window';
import {
	useSearchStore,
	convertToSearchTotals,
	convertToSearchProgress,
	convertToResults,
} from '../../../stores/search-store';
import { useSearch } from '../../../hooks/use-search';
import { useMessageStore } from '../../../stores/message-store';
import type { Result as ResultType } from '../../../types/search';
import './style.scss';

interface Progress {
	next: number | false;
	previous: number | false;
	current?: number;
	rows?: number;
}

/**
 * Normalize a progress value (next or previous) from API format to local format.
 * Converts boolean values to false, preserves numbers (including 0), and defaults undefined to false.
 *
 * @param value - The progress value from the API (boolean, number, or undefined)
 * @return The normalized value (number or false)
 */
function normalizeProgressValue( value: boolean | number | undefined ): number | false {
	if ( value === false || value === true ) {
		return false;
	}
	if ( value !== undefined ) {
		return value;
	}
	return false;
}

function convertProgressToLocal( progress: {
	next: boolean | number;
	previous?: boolean | number | undefined;
	current?: number;
	rows?: number;
} ): Progress {
	return {
		next: normalizeProgressValue( progress.next ),
		previous: normalizeProgressValue( progress.previous ),
		...( progress.current !== undefined && { current: progress.current } ),
		...( progress.rows !== undefined && { rows: progress.rows } ),
	};
}

function hasMoreResults(
	searchDirection: string | null,
	progress: { next: boolean | number; previous?: boolean | number | undefined }
): boolean {
	const next = normalizeProgressValue( progress.next );
	const prev = normalizeProgressValue( progress.previous );
	return (
		( searchDirection === SEARCH_FORWARD && next !== false ) ||
		( searchDirection === SEARCH_BACKWARD && prev !== false )
	);
}
const shouldLoadMore = ( requestCount: number, results: ResultType[], perPage: number | undefined ): boolean => {
	// Allow loading more if we're still paginating (requestCount > 0) and haven't reached the page limit
	// Don't check status here - status can be COMPLETE for individual requests but we still want to paginate
	return requestCount > 0 && perPage !== undefined && results.length < perPage;
};

function SearchResults() {
	const results = useSearchStore( ( state ) => state.results );
	const totals = useSearchStore( ( state ) => state.totals );
	const progress = useSearchStore( ( state ) => state.progress );
	const isBusy = useSearchStore( ( state ) => state.isBusy );
	const search = useSearchStore( ( state ) => state.search );
	const searchDirection = useSearchStore( ( state ) => state.searchDirection );
	const showLoading = useSearchStore( ( state ) => state.showLoading );
	const resultsDirty = useSearchStore( ( state ) => state.resultsDirty );
	const cumulativeMatchedRows = useSearchStore( ( state ) => state.cumulativeMatchedRows );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const addError = useMessageStore( ( state ) => state.addError );

	const [ requestCount, setRequestCount ] = useState( 0 );
	const searchMutation = useSearch();
	const { perPage } = search;
	const isAdvanced = isAdvancedSearch( search );

	// Initialize request count to 1 when results first arrive to trigger automatic pagination
	useEffect( () => {
		if (
			results.length > 0 &&
			requestCount === 0 &&
			isAdvanced &&
			! isBusy &&
			hasMoreResults( searchDirection ?? SEARCH_FORWARD, progress )
		) {
			// Results have arrived and no requests in progress, start the sliding window
			setRequestCount( 1 );
		} else if ( results.length === 0 && requestCount > 0 ) {
			// Results cleared, reset count
			setRequestCount( 0 );
		}
	}, [ results.length, requestCount, isAdvanced, isBusy, searchDirection, progress ] );

	const onSearchMore = ( page: number | false, pageSize: number, limit: number ) => {
		if ( page === false ) {
			return;
		}

		setStatus( STATUS_IN_PROGRESS );

		searchMutation.mutate(
			{
				...search,
				page,
				perPage: pageSize,
				limit,
				searchDirection: searchDirection || SEARCH_FORWARD,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Convert API results (number row_id) to Result[] (string row_id)

					// Get fresh results to append to
					const currentResults = useSearchStore.getState().results;
					setResults( [ ...currentResults, ...convertToResults( data.results ) ] );
					setTotals( convertToSearchTotals( data.totals ) );
					setProgress( convertToSearchProgress( data.progress ) );
					setStatus( data.status ?? STATUS_COMPLETE );

					// Increment request count AFTER updating progress to trigger next request with fresh data
					setRequestCount( ( prev ) => prev + 1 );
				},
			}
		);
	};

	const onError = () => {
		setStatus( STATUS_FAILED );
		addError( __( 'Your search resulted in too many requests. Please narrow your search terms.', 'search-regex' ) );
	};
	const isLoading = isBusy;
	const shouldLoad = shouldLoadMore( requestCount, results, perPage );
	const hasMore = hasMoreResults( searchDirection ?? SEARCH_FORWARD, progress );
	// Sliding window enabled when we have results and haven't filled the page yet
	// Also check requestCount > 0 to prevent firing immediately after manual navigation clears results
	const canLoad = isAdvanced && perPage !== undefined && shouldLoad && hasMore && requestCount > 0;

	const perPageValue = perPage ?? 25;

	// For advanced searches, calculate matched_rows from actual results loaded
	// since the API returns 0 during the sliding window.
	// The total is: cumulative matches from previous pages + matches in current page
	const effectiveTotals = isAdvanced
		? {
				...totals,
				matched_rows: cumulativeMatchedRows + results.length,
		  }
		: totals;

	useSlidingSearchWindow(
		canLoad,
		requestCount,
		perPageValue,
		( size: number ) => {
			// Get fresh values from store to avoid stale closure
			const currentProgress = useSearchStore.getState().progress;
			const currentSearchDirection = useSearchStore.getState().searchDirection;
			const currentResults = useSearchStore.getState().results;

			const nextVal = normalizeProgressValue( currentProgress.next );
			const prevVal = normalizeProgressValue( currentProgress.previous );
			const currentPageValue = currentSearchDirection === SEARCH_FORWARD ? nextVal : prevVal;
			const limit = perPageValue - currentResults.length;

			onSearchMore( currentPageValue, size, limit );
		},
		onError
	);

	return (
		<>
			<Pagination
				totals={ effectiveTotals }
				perPage={ perPage ?? 25 }
				isLoading={ isLoading }
				progress={ convertProgressToLocal( progress ) }
				searchDirection={ searchDirection || SEARCH_FORWARD }
				advanced={ isAdvanced }
				resultsDirty={ resultsDirty }
			/>

			<table className={ clsx( 'wp-list-table', 'widefat', 'fixed', 'striped', 'items', 'searchregex-results' ) }>
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
				totals={ effectiveTotals }
				perPage={ perPage ?? 25 }
				isLoading={ isLoading }
				progress={ convertProgressToLocal( progress ) }
				searchDirection={ searchDirection || SEARCH_FORWARD }
				noTotal
				advanced={ isAdvanced }
				resultsDirty={ resultsDirty }
			/>
		</>
	);
}

export default SearchResults;
