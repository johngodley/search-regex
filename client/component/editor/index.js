/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';
import TextareaAutosize from 'react-textarea-autosize';

/**
 * Internal dependencies
 */

import { Modal, Select, Spinner } from 'wp-plugin-components';
import { loadRow, saveRow } from 'state/search/action';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import './style.scss';

function Editor( { result, onClose, onLoad, rawData, onSave, status } ) {
	const { row_id, source_type, columns } = result;
	const [ column, setColumn ] = useState( columns[0].column_id );
	const [ content, setContent ] = useState( '' );
	const columnLabel = columns.find( item => item.column_id === column ) ? columns.find( item => item.column_id === column ).column_label : '';

	const save = () => {
		onClose();
		onSave( source_type, row_id, column, content );
	};

	const changeColumn = newColumn => {
		setColumn( newColumn );
		setContent( rawData[ newColumn ] ? rawData[ newColumn ] : '' );
	}

	useEffect( () => {
		onLoad( source_type, row_id );
	}, [] );

	useEffect( () => {
		if ( rawData ) {
			setContent( rawData[ column ] ? rawData[ column ] : '' );
		}
	}, [ rawData ] );

	if ( ! rawData ) {
		return null;
	}

	return (
		<Modal onClose={ onClose }>
			<div className="searchregex-editor">
				<h2>
					{ __( 'Editing %s', {
						args: columnLabel,
					} ) }
				</h2>

				<TextareaAutosize
					value={ content }
					rows={ 15 }
					maxRows={ 30 }
					onChange={ ( ev ) => setContent( ev.target.value ) }
					disabled={ status === STATUS_IN_PROGRESS }
				/>

				<div className="searchregex-editor__actions">
					{ columns.length === 1 && <div>&nbsp;</div> }
					{ columns.length > 1 && (
						<Select
							name="column_id"
							value={ column }
							items={ columns.map( ( { column_id, column_label } ) => ( {
								value: column_id,
								label: column_label,
							} ) ) }
							onChange={ ( ev ) => changeColumn( ev.target.value ) }
							disabled={ status === STATUS_IN_PROGRESS }
						/>
					) }

					<div>
						{ status === STATUS_IN_PROGRESS && <Spinner /> }
						<button
							disabled={ status === STATUS_IN_PROGRESS }
							className="button button-primary"
							onClick={ save }
							type="button"
						>
							{ __( 'Save' ) }
						</button>
						<button className="button button-secondary" onClick={ () => onClose() } type="button">
							{ __( 'Close' ) }
						</button>
					</div>
				</div>
			</div>
		</Modal>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onLoad: ( sourceName, rowId ) => {
			dispatch( loadRow( sourceName, rowId ) );
		},
		onSave: ( sourceName, rowId, columnId, content ) => {
			dispatch( saveRow( sourceName, rowId, columnId, content ) );
		},
	};
}

function mapStateToProps( state ) {
	const { rawData, status } = state.search;

	return {
		rawData,
		status,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Editor );
