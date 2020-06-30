/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import './style.scss';
import { Select } from 'wp-plugin-components';
import { setPreset, savePreset, updatePreset } from 'state/preset/action';
import { DropdownMenu, Modal } from 'wp-plugin-components';
import { STATUS_IN_PROGRESS } from 'state/settings/type';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

/**
 *
 * @param {object} props
 * @param {PresetValue[]} props.presets - Presets
 * @param {string} props.status - Status
 * @param {string} props.search - Search string
 * @param {string} props.currentPreset - Current preset
 * @param {} props.onChangePreset
 * @param {} props.onSavePreset
 * @param {} props.onUpdatePreset
 */
function Presets( props ) {
	const { presets, currentPreset, onChangePreset, onSavePreset, search, status, onUpdatePreset } = props;
	const [ askName, showName ] = useState( false );
	const [ presetName, setPresetName ] = useState( '' );
	const savingItem = [
		{
			label: __( 'Saving preset' ),
			value: '',
		},
	];
	const items = [ { label: __( 'No preset' ), value: '' } ].concat(
		presets.map( ( item ) => ( {
			label: item.name.substr( 0, 50 ),
			value: item.id,
		} ) )
	);
	const askPresetName = ( ev ) => {
		ev.preventDefault();
		showName( true );
		setPresetName( '' );
	};
	const savePreset = ( ev ) => {
		ev.preventDefault();
		onSavePreset( presetName, search );
		showName( false );
		setPresetName( '' );
	};
	const changePreset = ( ev ) => {
		const preset = presets.find( ( item ) => item.id === ev.target.value );

		onChangePreset( preset );
	};
	const updatePreset = ( ev ) => {
		ev.preventDefault();
		onUpdatePreset( currentPreset, search );
	};

	const presetActions = [];

	if ( ! currentPreset ) {
		presetActions.push(
			<a href="#" onClick={ askPresetName }>
				{ __( 'Save search as new preset' ) }
			</a>
		);
	}

	if ( currentPreset ) {
		presetActions.push(
			<a href="#" onClick={ updatePreset }>
				{ __( 'Update current preset' ) }
			</a>
		);
	}

	return (
		<div className="searchregex-saved">
			{ askName && (
				<Modal onClose={ () => showName( false ) } className="searchregex-preset__name">
					<h2>{ __( 'Saving Preset' ) }</h2>
					<p>{ __( 'Enter a name for your preset' ) }</p>
					<form onSubmit={ savePreset }>
						<input
							type="text"
							name="name"
							autoFocus
							value={ presetName }
							onChange={ ( ev ) => setPresetName( ev.target.value ) }
							placeholder={ __( 'Enter preset name' ) }
						/>
						<input
							type="submit"
							className="button button-primary"
							disabled={ presetName.length === 0 }
							value={ __( 'Save' ) }
						/>
						<button className="button button-secondary" onClick={ () => showName( false ) } type="button">
							{ __( 'Cancel' ) }
						</button>
					</form>
				</Modal>
			) }
			<Select
				name="saved-search"
				value={ currentPreset }
				disabled={ status === STATUS_IN_PROGRESS }
				items={ status === STATUS_IN_PROGRESS ? savingItem : items }
				onChange={ changePreset }
			/>

			<DropdownMenu menu={ presetActions } align="left" disabled={ status === STATUS_IN_PROGRESS } />
		</div>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		/**
		 * @param {string} id - Preset name
		 * @param {SearchValues} searchValues - The preset search values
		 */
		onChangePreset: ( preset ) => {
			dispatch( setPreset( preset ) );
		},

		/**
		 * @param {string} name - Preset name
		 * @param {SearchValues} searchValues - The preset search values
		 */
		onSavePreset: ( name, searchValues ) => {
			dispatch( savePreset( name, searchValues ) );
		},

		/**
		 * @param {string} id - Preset ID
		 * @param {SearchValues} searchValues - The preset search values
		 */
		onUpdatePreset: ( id, searchValues ) => {
			dispatch( updatePreset( id, searchValues ) );
		},
	};
}

function mapStateToProps( state ) {
	const { presets, currentPreset, status } = state.preset;
	const { search } = state.search;

	return {
		presets,
		currentPreset,
		search,
		status,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Presets );
