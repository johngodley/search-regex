/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Dropdown from 'component/dropdown';
import Replace from 'component/replace';
import ExternalLink from 'component/external-link';
import { replaceRow } from 'state/search/action';
import { deleteRow } from 'state/search/action';
import { STATUS_IN_PROGRESS } from 'state/settings/type';

function Actions( { setReplacement, actions, isLoading, onSave, result, onDelete, onEditor, description } ) {
	const reset = ( toggle ) => {
		toggle();
		setReplacement( '' );
	};
	const save = ( value, toggle ) => {
		toggle();
		setReplacement( '' );
		onSave( value, result.row_id );
	};
	const clicked = ( ev, toggle ) => {
		ev.preventDefault();
		if ( ! isLoading ) {
			toggle();
		}
	};
	const deleteTheRow = ( ev ) => {
		ev.preventDefault();
		onDelete( result.source_type, result.row_id );
	};
	const editor = ( ev ) => {
		ev.preventDefault();
		onEditor();
	};

	const actionList = [
		<Dropdown
			key="replace"
			renderToggle={ ( isOpen, toggle ) => (
				<a href="#" onClick={ ( ev ) => clicked( ev, toggle ) }>{ __( 'Replace' ) }</a>
			) }
			onHide={ () => setReplacement( '' ) }
			hasArrow
			disabled={ isLoading }
			align="right"
			renderContent={ ( toggle ) => (
				<Replace
					className="searchregex-replace__modal"
					canReplace
					setReplace={ ( replace ) => setReplacement( replace ) }
					autoFocus
					onSave={ ( value ) => save( value, toggle ) }
					onCancel={ () => reset( toggle ) }
					placeholder={ __( 'Replacement for all matches in this row' ) }
					description={ description }
				/>
			) }
		/>
	];

	const actionMap = {
		edit: __( 'Edit' ),
	};
	const actionKeys = Object.keys( actions );

	for ( let index = 0; index < actionKeys.length; index++ ) {
		if ( actionMap[ actionKeys[ index ] ] ) {
			actionList.push( <ExternalLink url={ actions[ actionKeys[ index ] ] } key={ actionKeys[ index ] }>{ actionMap[ actionKeys[ index ] ] }</ExternalLink> );
		}
	}

	actionList.push( <a key="edit" href="#" onClick={ editor }>{ __( 'Editor' ) }</a> );
	actionList.push( <a key="delete" href="#" onClick={ deleteTheRow }>{ __( 'Delete' ) }</a> );

	return actionList.reduce( ( prev, curr ) => [ prev, ' | ', curr ] );
}

function mapStateToProps( state ) {
	const { status } = state;

	return {
		isLoading: status === STATUS_IN_PROGRESS,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onDelete: ( sourceName, rowId ) => {
			dispatch( deleteRow( sourceName, rowId ) );
		},
		onSave: ( replacement, rowId ) => {
			dispatch( replaceRow( replacement, rowId ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Actions );
