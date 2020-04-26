
/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Spinner from 'component/spinner';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { SEARCH_FORWARD } from 'state/search/type';
import { cancel, search, replaceAll } from 'state/search/action';

const canSearch = ( status, searchPhrase ) => status === STATUS_IN_PROGRESS || searchPhrase.length === 0;
const canReplace = ( status, searchPhrase, replacement ) => status === STATUS_IN_PROGRESS || searchPhrase.length === 0 || searchPhrase === replacement || ( replacement !== null && replacement.length === 0 );

const REPLACE_SIZE = 50;

function SearchActions( { search, status, onSearch, onReplace, onCancel, replaceAll, canCancel } ) {
	const { searchPhrase, replacement } = search;

	return (
		<div className="searchregex-search__action">
			<input
				className="button button-primary"
				type="submit"
				value={ __( 'Search' ) }
				onClick={ () => onSearch( search, 0, SEARCH_FORWARD ) }
				disabled={ canSearch( status, searchPhrase ) || replaceAll }
			/>

			<input
				className="button button-delete"
				type="submit"
				value={ __( 'Replace All' ) }
				onClick={ () => onReplace( search, 0, REPLACE_SIZE ) }
				disabled={ canReplace( status, searchPhrase, replacement ) || replaceAll }
			/>

			{ status === STATUS_IN_PROGRESS && canCancel && (
				<>
					<button
						className="button button-delete"
						onClick={ onCancel }
					>
						{ __( 'Cancel' ) }
					</button>

					<Spinner />
				</>
			) }
		</div>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onCancel: () => {
			dispatch( cancel() );
		},
		onSearch: ( searchValue, page, direction ) => {
			dispatch( search( searchValue, page, direction ) );
		},
		onReplace: ( replace, page, perPage ) => {
			dispatch( replaceAll( replace, page, perPage ) );
		},
	};
}

function mapStateToProps( state ) {
	const { search, status, replaceAll, canCancel } = state.search;

	return {
		search,
		status,
		replaceAll,
		canCancel,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchActions );
