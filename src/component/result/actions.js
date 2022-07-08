/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import { ExternalLink } from '@wp-plugin-components';
import { deleteRow } from '../../state/search/action';

/**
 *
 * @param {object} props - Component props
 */
function Actions( props ) {
	const { result, disabled } = props;
	const dispatch = useDispatch();
	const { actions } = result;
	const actionList = [];
	const actionMap = {
		edit: __( 'Edit', 'search-regex' ),
		view: __( 'View', 'search-regex' ),
	};

	function onDelete( ev ) {
		ev.preventDefault();
		dispatch( deleteRow( result.source_type, result.row_id ) );
	}

	const actionKeys = Object.keys( actions );
	for ( let index = 0; index < actionKeys.length; index++ ) {
		if ( actionMap[ actionKeys[ index ] ] ) {
			actionList.push(
				<ExternalLink url={ actions[ actionKeys[ index ] ] } key={ actionKeys[ index ] }>
					{ actionMap[ actionKeys[ index ] ] }
				</ExternalLink>
			);
		}
	}

	actionList.push(
		<a key="delete" href="#" onClick={ onDelete }>
			{ __( 'Delete database row', 'search-regex' ) }
		</a>
	);

	return (
		<div className="row-actions">
			{ disabled ? <>&nbsp;</> : actionList.reduce( ( prev, curr ) => [ prev, ' | ', curr ] ) }
		</div>
	);
}

export default Actions;
