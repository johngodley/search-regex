import { type MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wp-plugin-components';
import { useDeleteRow } from '../../hooks/use-search';
import { useSearchStore } from '../../stores/search-store';
import type { Result } from '../../types/search';

interface ActionsProps {
	result: Result;
	disabled: boolean;
}

function Actions( props: ActionsProps ): JSX.Element {
	const { result, disabled } = props;
	const { actions, source_type: sourceType, row_id: rowId } = result;
	const deleteRowMutation = useDeleteRow();

	const results = useSearchStore( ( state ) => state.results );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const actionList: JSX.Element[] = [];
	const actionMap: Record< string, string > = {
		edit: __( 'Edit', 'search-regex' ),
		view: __( 'View', 'search-regex' ),
	};

	function onDelete( ev: MouseEvent< HTMLButtonElement > ): void {
		ev.preventDefault();

		deleteRowMutation.mutate(
			{ source: sourceType, rowId },
			{
				onSuccess: () => {
					// Remove the deleted row from results
					setResults( results.filter( ( r: any ) => r.row_id !== rowId ) );
				},
			}
		);
	}

	const actionKeys = Object.keys( actions );
	for ( let index = 0; index < actionKeys.length; index++ ) {
		const key = actionKeys[ index ];
		if ( ! key ) {
			continue;
		}
		const actionUrl = actions[ key ];
		const actionLabel = actionMap[ key ];
		if ( actionLabel && actionUrl ) {
			actionList.push(
				<ExternalLink url={ actionUrl as string } key={ key }>
					{ actionLabel }
				</ExternalLink>
			);
		}
	}

	actionList.push(
		<button key="delete" type="button" onClick={ onDelete } className="button-link">
			{ __( 'Delete database row', 'search-regex' ) }
		</button>
	);

	return (
		<div className="row-actions">
			{ disabled ? (
				<>&nbsp;</>
			) : (
				actionList.reduce< ( JSX.Element | string )[] >(
					( prev, curr, index ) => ( index === 0 ? [ curr ] : [ ...prev, ' | ', curr ] ),
					[]
				)
			) }
		</div>
	);
}

export default Actions;
