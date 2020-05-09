/**
 * External dependencies
 */

import React, { useEffect } from 'react';
import { translate as __ } from 'lib/locale';
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
import { searchMore, setError, cancel } from 'state/search/action';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import './style.scss';

const hasMoreResults = ( searchDirection, progress ) => ( searchDirection === SEARCH_FORWARD && progress.next !== false ) || ( searchDirection === SEARCH_BACKWARD && progress.previous !== false );
const shouldLoadMore = ( status, requestCount, results, perPage ) => status === STATUS_IN_PROGRESS && requestCount > 0 && results.length < perPage;

const MAX_REQUESTS = 500;
const SEARCH_MORE_DELAY = 450;

function SearchResults( props ) {
	const { results, totals, progress, status, requestCount, search, searchDirection, showLoading, onCancel } = props;
	const { perPage, searchFlags } = search;
	const { onSearchMore, onChangePage, onSetError } = props;
	const isLoading = status === STATUS_IN_PROGRESS;

	useEffect( () => {
		if ( requestCount > MAX_REQUESTS ) {
			onSetError( __( 'Maximum number of page requests has been exceeded and the search stopped. Try to be more specific with your search term.' ) );
		} else if ( searchFlags.regex && shouldLoadMore( status, requestCount, results, perPage ) && hasMoreResults( searchDirection, progress ) ) {
			setTimeout( () => {
				onSearchMore( search, searchDirection === SEARCH_FORWARD ? progress.next : progress.previous, perPage - results.length );
			}, SEARCH_MORE_DELAY );
		} else if ( searchFlags.regex && ! hasMoreResults( searchDirection, progress ) ) {
			onCancel();
		}
	}, [ requestCount ] );

	return (
		<>
			<Pagination
				totals={ totals }
				onChangePage={ onChangePage }
				perPage={ perPage }
				isLoading={ isLoading }
				progress={ progress }
				searchDirection={ searchDirection }
			/>

			<table className={ classnames( 'wp-list-table', 'widefat', 'fixed', 'striped', 'items', 'searchregex-results' ) }>
				<thead>
					<tr>
						<th className="searchregex-result__table">{ __( 'Source' ) }</th>
						<th className="searchregex-result__row">{ __( 'Row ID' ) }</th>
						<th className="searchregex-result__matches">{ __( 'Matches' ) }</th>
						<th className="searchregex-result__match">{ __( 'Matched Phrases' ) }</th>
						<th className="searchregex-result__action">{ __( 'Actions' ) }</th>
					</tr>
				</thead>

				<tbody>
					{ results.map( ( result, pos ) => (
						<Result key={ pos } result={ result } /> )
					) }

					{ showLoading && <TableLoading columns={ 4 } /> }

					{ ! isLoading && results.length === 0 && <EmptyResults columns={ 5 } /> }
				</tbody>
			</table>

			<Pagination
				totals={ totals }
				onChangePage={ onChangePage }
				perPage={ perPage }
				isLoading={ isLoading }
				progress={ progress }
				searchDirection={ searchDirection }
				noTotal
			/>
		</>
	);
}

function mapStateToProps( state ) {
	const { results, status, progress, totals, requestCount, searchDirection, search, showLoading } = state.search;

	return {
		results,
		status,
		progress,
		searchDirection,
		totals,
		requestCount,
		search,
		showLoading,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onSearchMore: ( searchValue, page, limit ) => {
			dispatch( searchMore( searchValue, page, limit ) );
		},
		onSetError: ( error ) => {
			dispatch( setError( error ) );
		},
		onCancel: () => {
			dispatch( cancel() );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchResults );
