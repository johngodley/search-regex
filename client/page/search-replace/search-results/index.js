/**
 * External dependencies
 */

import React, { useEffect } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
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
import { throttle, adjustPerPage } from 'lib/result-window';
import './style.scss';

const hasMoreResults = ( searchDirection, progress ) => ( searchDirection === SEARCH_FORWARD && progress.next !== false ) || ( searchDirection === SEARCH_BACKWARD && progress.previous !== false );
const shouldLoadMore = ( status, requestCount, results, perPage ) => status === STATUS_IN_PROGRESS && requestCount > 0 && results.length < perPage;

const MAX_REQUESTS = 1000;

function SearchResults( props ) {
	const { results, totals, progress, status, requestCount, search, searchDirection, showLoading, onCancel, actionDropdown } = props;
	const { perPage, searchFlags } = search;
	const { onSearchMore, onChangePage, onSetError } = props;
	const isLoading = status === STATUS_IN_PROGRESS;

	useEffect( () => {
		if ( requestCount > MAX_REQUESTS ) {
			onSetError( __( 'Maximum number of page requests has been exceeded and the search stopped. Try to be more specific with your search term.' ) );
		} else if ( searchFlags.regex ) {
			if ( shouldLoadMore( status, requestCount, results, perPage ) && hasMoreResults( searchDirection, progress ) ) {
				const searchSize = adjustPerPage( requestCount, perPage );
				const page = searchDirection === SEARCH_FORWARD ? progress.next : progress.previous;

				throttle( () => onSearchMore( page, searchSize, perPage - results.length ) );
			} else if ( ! shouldLoadMore( status, requestCount, results, perPage ) && requestCount > 0 ) {
				onCancel();
			}
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
				advanced={ !! searchFlags.regex }
			/>

			<table className={ classnames( 'wp-list-table', 'widefat', 'fixed', 'striped', 'items', 'searchregex-results' ) }>
				<thead>
					<tr>
						<th className="searchregex-result__table">{ __( 'Source' ) }</th>
						<th className="searchregex-result__row">{ __( 'Row ID' ) }</th>
						<th className="searchregex-result__matches">{ __( 'Matches' ) }</th>
						<th className="searchregex-result__match">{ __( 'Matched Phrases' ) }</th>
						<th className={ classnames( 'searchregex-result__action', actionDropdown && 'searchregex-result__action__dropdown' ) }>{ __( 'Actions' ) }</th>
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
				advanced={ !! searchFlags.regex }
			/>
		</>
	);
}

function mapStateToProps( state ) {
	const { results, status, progress, totals, requestCount, searchDirection, search, showLoading } = state.search;
	const { actionDropdown } = state.settings.values;

	return {
		results,
		status,
		progress,
		searchDirection,
		totals,
		requestCount,
		search,
		showLoading,
		actionDropdown,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onSearchMore: ( page, perPage, limit ) => {
			dispatch( searchMore( page, perPage, limit ) );
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
