/**
 * Internal dependencies
 */
import {
	PRESET_SAVE,
	PRESET_SAVED,
	PRESET_SAVE_FAIL,
	PRESET_SELECT,
	PRESET_UPLOAD,
	PRESET_UPLOAD_COMPLETE,
	PRESET_SET_CLIPBOARD,
	PRESET_CLIPBOARD_FAIL,
	PRESET_UPLOAD_FAIL,
	PRESET_CLEAR,
} from './type';
import SearchRegexApi from '../../lib/api-request';
import { apiFetch } from '@wp-plugin-lib';
import { getPreset } from './selector';

/** @typedef {import('../state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('../state/preset/type.js').PresetValue} PresetValue */

/**
 * Save the preset.
 *
 * @param {string} name Name for this search
 * @param {SearchValues} searchValues Search values
 */
export const savePreset = ( name, searchValues ) => ( dispatch, getState ) => {
	return createPreset( dispatch, name, searchValues );
};

/**
 * Update a preset.
 *
 * @param {string} id - Id of the preset
 * @param {PresetValue} preset - Preset values
 */
export const updatePreset = ( id, preset ) => ( dispatch, getState ) => {
	const updatedPreset = {
		...getPreset( getState().preset.presets, id ),
		...preset,
	};

	dispatch( { type: PRESET_SAVE, id, preset } );

	return apiFetch( SearchRegexApi.preset.update( updatedPreset ) )
		.then( ( json ) => {
			dispatch( { type: PRESET_SAVED, ...json } );
		} )
		.catch( ( error ) => {
			dispatch( { type: PRESET_SAVE_FAIL, error } );
		} );
};

/**
 * Delete a preset.
 *
 * @param {string} id Id of the preset
 */
export const deletePreset = ( id ) => ( dispatch ) => {
	dispatch( { type: PRESET_SAVE } );

	return apiFetch( SearchRegexApi.preset.delete( id ) )
		.then( ( json ) => {
			dispatch( { type: PRESET_SAVED, ...json } );
		} )
		.catch( ( error ) => {
			dispatch( { type: PRESET_SAVE_FAIL, error } );
		} );
};

/**
 * Set the current preset
 *
 * @param {string} presetId
 * @param {SearchValues} searchValues
 */
export const setPreset = ( preset, currentOnly = false ) => ( { type: PRESET_SELECT, preset, currentOnly } );

export const clearPresetError = () => ( { type: PRESET_CLEAR } );

export const setClipboard = ( clipboard ) => ( { type: PRESET_SET_CLIPBOARD, clipboard } );

export const exportPresets = () => ( dispatch ) => {
	const request = SearchRegexApi.preset.export();

	document.location.href = apiFetch.getUrl( request.url );

	dispatch( { type: PRESET_CLEAR } );
};

export const uploadPreset = ( file ) => ( dispatch ) => {
	dispatch( { type: PRESET_UPLOAD } );

	return apiFetch( SearchRegexApi.preset.upload( file ) )
		.then( ( json ) => {
			dispatch( { type: PRESET_UPLOAD_COMPLETE, ...json } );
		} )
		.catch( ( error ) => {
			dispatch( { type: PRESET_UPLOAD_FAIL, error } );
		} );
};

export const importClipboard = ( text ) => ( dispatch ) => {
	try {
		const json = JSON.parse( text );
		const presets = Array.isArray( json ) ? json : [ json ];

		return uploadPreset( new File( [ new Blob( [ JSON.stringify( presets ) ] ) ], 'preset.json' ) )( dispatch );
	} catch ( error ) {
		return dispatch( { type: PRESET_CLIPBOARD_FAIL, error, errorContext: text } );
	}
};

function createPreset( dispatch, name, searchValues ) {
	dispatch( { type: PRESET_SAVE } );

	return apiFetch( SearchRegexApi.preset.save( searchValues, name ) )
		.then( ( json ) => {
			dispatch( { type: PRESET_SAVED, ...json } );
		} )
		.catch( ( error ) => {
			dispatch( { type: PRESET_SAVE_FAIL, error } );
		} );
}
