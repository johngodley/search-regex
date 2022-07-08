/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import { Spinner, Button } from '@wp-plugin-components';
import { STATUS_IN_PROGRESS } from '../../state/settings/type';
import { cancel, perform } from '../../state/search/action';

function isPerformReady( action, actionOption ) {
	if ( action === 'delete' ) {
		return true;
	}

	if ( action === 'modify' ) {
		return actionOption.length > 0;
	}

	if ( action === 'action' ) {
		return actionOption.hook && actionOption.hook.length > 0;
	}

	if ( action === 'export' || action === 'global' ) {
		return true;
	}

	return false;
}

function getPerformButton( action ) {
	if ( action === 'delete' ) {
		return __( 'Delete Matches', 'search-regex' );
	}

	if ( action === 'export' ) {
		return __( 'Export Matches', 'search-regex' );
	}

	if ( action === 'action' ) {
		return __( 'Run Action', 'search-regex' );
	}

	return __( 'Replace All', 'search-regex' );
}

/**
 * Search actions
 *
 * @param {object} props - Component props
 */
function SearchActions( props ) {
	const { search, status, canCancel, resultsDirty, isSaving } = useSelector( ( state ) => state.search );
	const { action, actionOption } = search;
	const dispatch = useDispatch();

	return (
		<div className="searchregex-search__action">
			<Button isPrimary isSubmit disabled={ status === STATUS_IN_PROGRESS || isSaving } name="search">
				{ resultsDirty ? __( 'Refresh', 'search-regex' ) : __( 'Search', 'search-regex' ) }
			</Button>

			{ action !== '' && (
				<Button
					isDestructive
					disabled={ ! isPerformReady( action, actionOption ) || status === STATUS_IN_PROGRESS || isSaving }
					onClick={ () => dispatch( perform( 0 ) ) }
				>
					{ getPerformButton( action ) }
				</Button>
			) }

			{ ( status === STATUS_IN_PROGRESS || isSaving ) && canCancel && (
				<>
					&nbsp;
					<Button isDestructive onClick={ () => dispatch( cancel() ) }>
						{ __( 'Cancel', 'search-regex' ) }
					</Button>
					<Spinner />
				</>
			) }
		</div>
	);
}


export default SearchActions;
