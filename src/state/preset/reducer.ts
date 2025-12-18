import type { PresetValue } from '../../types/preset';
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
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from '../settings/type';

interface PresetState {
	presets: PresetValue[];
	currentPreset: string;
	uploadStatus: string | null;
	isUploading: boolean;
	clipboardStatus: string | null;
	clipboard: string;
	error: unknown | null;
	errorContext: string | null;
	imported: number;
}

interface PresetAction {
	type: string;
	id?: string;
	preset?: Partial< PresetValue >;
	presets?: PresetValue[];
	current?: { id: string };
	clipboard?: string;
	error?: unknown;
	errorContext?: string;
	import?: number;
}

function updatePresets( state: PresetValue[], action: PresetAction ): PresetValue[] {
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

export default function presets(
	state: PresetState = {
		presets: [],
		currentPreset: '',
		uploadStatus: null,
		isUploading: false,
		clipboardStatus: null,
		clipboard: '',
		error: null,
		errorContext: null,
		imported: 0,
	},
	action: PresetAction
): PresetState {
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
				clipboard: action.clipboard || '',
			};

		case PRESET_CLIPBOARD_FAIL:
			return {
				...state,
				clipboardStatus: STATUS_FAILED,
				errorContext: action.errorContext || null,
				error: action.error || null,
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
				presets: action.presets || state.presets,
				imported: action.import || 0,
				uploadStatus: STATUS_COMPLETE,
				isUploading: false,
				clipboard: '',
			};

		case PRESET_UPLOAD_FAIL:
			return {
				...state,
				uploadStatus: STATUS_FAILED,
				isUploading: false,
				error: action.error || null,
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
				presets: action.presets || state.presets,
				currentPreset: action.current?.id || state.currentPreset,
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
				currentPreset: action.preset ? ( action.preset as PresetValue ).id : '',
			};
	}

	return state;
}
