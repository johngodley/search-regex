import { type MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';
import type { ThunkDispatch } from 'redux-thunk';
import { ExternalLink } from '@wp-plugin-components';
import { deleteRow } from '../../state/search/action';
import type { RootState } from '../../state/reducers';
import type { Result } from '../../types/search';

interface ActionsProps {
	result: Result;
	disabled: boolean;
}

function Actions( props: ActionsProps ): JSX.Element {
	const { result, disabled } = props;
	const dispatch = useDispatch< ThunkDispatch< RootState, unknown, any > >();
	const { actions, source_type: sourceType, row_id: rowId } = result;
	const actionList: JSX.Element[] = [];
	const actionMap: Record< string, string > = {
		edit: __( 'Edit', 'search-regex' ),
		view: __( 'View', 'search-regex' ),
	};

	function onDelete( ev: MouseEvent< HTMLButtonElement > ): void {
		ev.preventDefault();
		void dispatch( deleteRow( sourceType, rowId ) );
	}

	const actionKeys = Object.keys( actions );
	for ( let index = 0; index < actionKeys.length; index++ ) {
		if ( actionMap[ actionKeys[ index ] ] && actions[ actionKeys[ index ] ] ) {
			actionList.push(
				<ExternalLink url={ actions[ actionKeys[ index ] ] as string } key={ actionKeys[ index ] }>
					{ actionMap[ actionKeys[ index ] ] }
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
