/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Result from 'component/result';
import TableLoading from './table-loading';
import EmptyResults from './empty-results';
import Pagination from '../pagination';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { searchMore, setError } from 'state/search/action';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import { isAdvancedSearch } from 'state/search/selector';
import { useSlidingSearchWindow } from 'lib/result-window';
import './style.scss';

const hasMoreResults = ( searchDirection, progress ) =>
	( searchDirection === SEARCH_FORWARD && progress.next !== false ) ||
	( searchDirection === SEARCH_BACKWARD && progress.previous !== false );
const shouldLoadMore = ( status, requestCount, results, perPage ) =>
	status === STATUS_IN_PROGRESS && requestCount > 0 && results.length < perPage;

function SearchResults( props ) {
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
		( size ) =>
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
						<th className="searchregex-result__table">{ __( 'Source' ) }</th>
						<th className="searchregex-result__row">{ __( 'Row ID' ) }</th>
						<th className="searchregex-result__match">{ __( 'Matched Content' ) }</th>
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

function mapStateToProps( state ) {
	const {
		results,
		status,
		progress,
		totals,
		requestCount,
		searchDirection,
		search,
		showLoading,
		resultsDirty,
	} = state.search;

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

function mapDispatchToProps( dispatch ) {
	return {
		onSearchMore: ( page, perPage, limit ) => {
			dispatch( searchMore( page, perPage, limit ) );
		},
		onError: () => {
			dispatch( setError( __( 'Your search resulted in too many requests. Please narrow your search terms.' ) ) );
		}
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchResults );
