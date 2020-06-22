/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';
import { CopyToClipboard } from 'react-copy-to-clipboard';

/**
 * Internal dependencies
 */

import { deletePreset, updatePreset } from 'state/preset/action';
import PresetEdit from './preset-edit';
import PresetEntry from './preset-entry';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

/**
 * @callback UpdatePresetCallback
 * @param {string} id - Preset ID
 * @param {PresetValue} preset - Preset
 */

/**
 * @callback DeleteCallback
 * @param {string} id - Preset ID
 */

function cleanPreset( preset ) {
	let newPreset = { ...preset };

	delete newPreset.id;
	if ( preset.tags.length === 0 ) {
		delete newPreset.tags;
	}

	if ( preset.locked.length === 0 ) {
		delete newPreset.locked;
	}

	return newPreset;
}

/**
 * A preset
 *
 * @param {object} props - Component props
 * @param {PresetValue} props.preset - Preset values
 * @param {DeleteCallback} props.onDelete - Callback to delete the preset
 * @param {UpdatePresetCallback} props.onUpdatePreset - Callback to update the preset
 */
function Preset( props ) {
	const [ isSaving, setSaving ] = useState( false );
	const [ isEditing, setEditing ] = useState( false );
	const { preset, onDelete, onUpdatePreset } = props;
	const { id } = preset;

	/** @param {MouseEvent} ev - Event */
	function deleteIt( ev ) {
		ev.preventDefault();

		if ( confirm( __( 'Are you sure you want to delete this preset?' ) ) ) {
			setSaving( true );
			onDelete( id );
		}
	}

	/** @param {MouseEvent} ev - Event */
	function editIt( ev ) {
		ev.preventDefault();
		setEditing( true );
	}

	/**
	 * Update a preset
	 * @param {PresetValue} preset - Preset to update
	 */
	function updateIt( preset ) {
		onUpdatePreset( id, preset );
		setEditing( false );
	}

	return (
		<tr className={ isSaving ? 'searchregex-preset__saving' : '' }>
			{ isEditing ? (
				<PresetEdit preset={ preset } onCancel={ () => setEditing( false ) } onUpdate={ updateIt } />
			) : (
				<PresetEntry preset={ preset }>
					<div className="row-actions">
						{ isSaving ? (
							<>&nbsp;</>
						) : (
							<>
								<a href="#" onClick={ editIt }>
									{ __( 'Edit' ) }
								</a>{' '}
								|{' '}
								<a href="#" onClick={ deleteIt }>
									{ __( 'Delete' ) }
								</a>{' '}
								|{' '}
								<CopyToClipboard text={ JSON.stringify( cleanPreset( preset ) ) }>
									<a href="#" onClick={ ( ev ) => ev.preventDefault() }>
										{ __( 'Copy to clipboard' ) }
									</a>
								</CopyToClipboard>
							</>
						) }
					</div>
				</PresetEntry>
			) }
		</tr>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		/**
		 * @param {string} id - Preset ID
		 */
		onDelete: ( id ) => {
			dispatch( deletePreset( id ) );
		},

		/**
		 * @param {string} id - Preset ID
		 * @param {Preset} preset - The preset
		 */
		onUpdatePreset: ( id, preset ) => {
			dispatch( updatePreset( id, preset ) );
		},
	};
}

export default connect(
	null,
	mapDispatchToProps
)( Preset );
