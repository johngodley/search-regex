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
import { searchMore, setError } from 'state/search/action';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import './style.scss';

const hasMoreResults = ( searchDirection, progress ) => ( searchDirection === SEARCH_FORWARD && progress.next !== false ) || ( searchDirection === SEARCH_BACKWARD && progress.previous !== false );
const shouldLoadMore = ( status, requestCount, results, perPage ) => status === STATUS_IN_PROGRESS && requestCount > 0 && results.length !== perPage;

const MAX_REQUESTS = 100;

function SearchResults( props ) {
	const { results, totals, progress, status, requestCount, search, searchDirection, showLoading } = props;
	const { searchFlags, perPage } = search;
	const { onSearchMore, onChangePage, onSetError } = props;
	const { matches = null, rows = null } = totals;
	const isLoading = status === STATUS_IN_PROGRESS;

	useEffect( () => {
		if ( requestCount > MAX_REQUESTS ) {
			onSetError( __( 'Maximum number of page requests has been exceeded and the search stopped. Try to be more specific with your search term.' ) );
		} else if ( Object.keys( searchFlags ).length > 0 && shouldLoadMore( status, requestCount, results, perPage ) && hasMoreResults( searchDirection, progress ) ) {
			onSearchMore( search, searchDirection === SEARCH_FORWARD ? progress.next : progress.previous );
		}
	}, [ requestCount ] );

	return (
		<>
			<Pagination
				matches={ matches }
				rows={ rows }
				onChangePage={ onChangePage }
				perPage={ perPage }
				isLoading={ isLoading }
				progress={ progress }
				searchDirection={ searchDirection }
			/>

			<table className={ classnames( 'wp-list-table', 'widefat', 'fixed', 'striped', 'items', 'searchregex-results' ) }>
				<thead>
					<tr>
						<th className="searchregex-result__table">{ __( 'Table' ) }</th>
						<th className="searchregex-result__row">{ __( 'Row ID' ) }</th>
						<th className="searchregex-result__matches">{ __( 'Matches' ) }</th>
						<th className="searchregex-result__match">{ __( 'Match' ) }</th>
						<th className="searchregex-result__action">{ __( 'Action' ) }</th>
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
		onSearchMore: ( searchValue, page ) => {
			dispatch( searchMore( searchValue, page ) );
		},
		onSetError: ( error ) => {
			dispatch( setError( error ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchResults );
