
/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { Spinner, Button } from 'wp-plugin-components';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { SEARCH_FORWARD } from 'state/search/type';
import { cancel, search, replaceAll } from 'state/search/action';

const canSearch = ( status, searchPhrase ) => status === STATUS_IN_PROGRESS || searchPhrase.length === 0;
const canReplace = ( status, searchPhrase, replacement ) => status === STATUS_IN_PROGRESS || searchPhrase.length === 0 || searchPhrase === replacement || ( replacement !== null && replacement.length === 0 );

const REPLACE_SIZE = 50;

/**
 * Search actions
 *
 * @param {object} props - Component props
 * @param {object} props.search - Search params
 * @param {string} props.status - Search status
 */
function SearchActions( { search, status, onSearch, onReplace, onCancel, replaceAll, canCancel } ) {
	const { searchPhrase, replacement } = search;

	return (
		<div className="searchregex-search__action">
			<Button
				isPrimary
				isSubmit
				onClick={ () => onSearch( 0, SEARCH_FORWARD ) }
				disabled={ canSearch( status, searchPhrase ) || replaceAll }
			>
				{ __( 'Search' ) }
			</Button>

			<Button
				isDestructive
				onClick={ () => onReplace( REPLACE_SIZE ) }
				disabled={ canReplace( status, searchPhrase, replacement ) || replaceAll }
			>
				{ __( 'Replace All' ) }
			</Button>

			{ status === STATUS_IN_PROGRESS && canCancel && (
				<>
					&nbsp;
					<Button isDestructive onClick={ onCancel }>
						{ __( 'Cancel' ) }
					</Button>

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
		onSearch: ( page, direction ) => {
			dispatch( search( page, direction ) );
		},
		onReplace: ( perPage ) => {
			dispatch( replaceAll( perPage ) );
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
