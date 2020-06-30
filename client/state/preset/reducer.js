/**
 * Internal dependencies
 */

import {
	PRESET_SAVED,
	PRESET_SELECT,
	PRESET_SAVE,
	PRESET_SAVE_FAIL,
	PRESET_UPLOAD,
	PRESET_UPLOAD_COMPLETE,
	PRESET_CLIPBOARD_FAIL,
	PRESET_SET_CLIPBOARD,
	PRESET_UPLOAD_FAIL,
	PRESET_CLEAR,
} from './type';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';

function updatePresets( state, action ) {
	if ( action.id ) {
		return state.map( ( preset ) => {
			if ( preset.id === action.id ) {
				return {
					...preset,
					...action.preset,
				};
			}

			return preset;
		} );
	}

	return state;
}

export default function presets( state = {}, action ) {
	switch ( action.type ) {
		case PRESET_CLEAR:
			return {
				...state,
				error: null,
				uploadStatus: null,
				clipboardStatus: null,
			};

		case PRESET_SET_CLIPBOARD:
			return {
				...state,
				clipboard: action.clipboard,
			};

		case PRESET_CLIPBOARD_FAIL:
			return {
				...state,
				clipboardStatus: STATUS_FAILED,
				errorContext: action.errorContext,
				error: action.error,
			};

		case PRESET_UPLOAD:
			return {
				...state,
				clipboardStatus: null,
				imported: 0,
				isUploading: true,
				uploadStatus: STATUS_IN_PROGRESS,
			};

		case PRESET_UPLOAD_COMPLETE:
			return {
				...state,
				presets: action.presets,
				imported: action.import,
				uploadStatus: STATUS_COMPLETE,
				isUploading: false,
				clipboard: '',
			};

		case PRESET_UPLOAD_FAIL:
			return {
				...state,
				uploadStatus: STATUS_FAILED,
				isUploading: false,
				error: action.error,
				clipboard: '',
				imported: 0,
			};

		case PRESET_SAVE:
			return {
				...state,
				uploadStatus: STATUS_IN_PROGRESS,
				presets: action.id ? updatePresets( state.presets, action ) : state.presets,
			};

		case PRESET_SAVED:
			return {
				...state,
				presets: action.presets,
				currentPreset: action.current.id,
				clipboardStatus: null,
				uploadStatus: STATUS_COMPLETE,
				isUploading: false,
				clipboard: '',
			};

		case PRESET_SAVE_FAIL:
			return {
				...state,
				uploadStatus: STATUS_FAILED,
			};

		case PRESET_SELECT:
			return {
				...state,
				currentPreset: action.preset ? action.preset.id : '',
			};
	}

	return state;
}
