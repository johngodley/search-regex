import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import { Spinner, Button } from '@wp-plugin-components';
import { STATUS_IN_PROGRESS } from '../../state/settings/type';
import { cancel, perform } from '../../state/search/action';

interface ActionOption {
	length?: number;
	hook?: string;
}

interface RootState {
	search: {
		search: {
			action: string;
			actionOption: ActionOption;
			replacement: string | null;
		};
		status: string;
		canCancel: boolean;
		resultsDirty: boolean;
		isSaving: boolean;
	};
}

function isPerformReady( action: string, actionOption: ActionOption, replacement: string | null ): boolean {
	if ( action === 'replace' && replacement === null ) {
		return true;
	}

	if ( action === 'replace' && replacement !== null && replacement.length > 0 ) {
		return true;
	}

	if ( action === 'delete' ) {
		return true;
	}

	if ( action === 'modify' ) {
		return actionOption.length !== undefined && actionOption.length > 0;
	}

	if ( action === 'action' ) {
		return actionOption.hook !== undefined && actionOption.hook.length > 0;
	}

	if ( action === 'export' || action === 'global' ) {
		return true;
	}

	return false;
}

function getPerformButton( action: string ): string {
	if ( action === 'delete' ) {
		return __( 'Delete Matching Rows', 'search-regex' );
	}

	if ( action === 'export' ) {
		return __( 'Export Matches', 'search-regex' );
	}

	if ( action === 'action' ) {
		return __( 'Run Action', 'search-regex' );
	}

	return __( 'Replace All', 'search-regex' );
}

function SearchActions() {
	const { search, status, canCancel, resultsDirty, isSaving } = useSelector( ( state: RootState ) => state.search );
	const { action, actionOption, replacement } = search;
	const dispatch = useDispatch();

	return (
		<div className="searchregex-search__action">
			<Button isPrimary isSubmit disabled={ status === STATUS_IN_PROGRESS || isSaving } name="search">
				{ resultsDirty ? __( 'Refresh', 'search-regex' ) : __( 'Search', 'search-regex' ) }
			</Button>

			{ action !== '' && (
				<Button
					isDestructive
					disabled={
						! isPerformReady( action, actionOption, replacement ) ||
						status === STATUS_IN_PROGRESS ||
						isSaving
					}
					onClick={ () => {
						/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
						dispatch( perform( 0 ) as any );
						/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
					} }
				>
					{ getPerformButton( action ) }
				</Button>
			) }

			{ ( status === STATUS_IN_PROGRESS || isSaving ) && canCancel && (
				<>
					&nbsp;
					<Button
						isDestructive
						onClick={ () => {
							/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
							dispatch( cancel() as any );
							/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
						} }
					>
						{ __( 'Cancel', 'search-regex' ) }
					</Button>
					<Spinner />
				</>
			) }
		</div>
	);
}

export default SearchActions;
