import type { ThunkAction } from 'redux-thunk';
import type { Dispatch } from 'redux';
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
import type { PresetValue } from '../../types/preset';
import type { SearchValues } from '../../types/search';
import SearchRegexApi from '../../lib/api-request';
import { apiFetch } from '@wp-plugin-lib';
import { getPreset } from './selector';

interface RootState {
	preset: {
		presets: PresetValue[];
		currentPreset: string;
	};
}

declare const SearchRegexi10n: {
	api: {
		WP_API_nonce: string;
	};
};

export const savePreset =
	( name: string, searchValues: SearchValues ): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch ) => {
		return createPreset( dispatch, name, searchValues );
	};

export const updatePreset =
	( id: string, preset: Partial< PresetValue > ): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch, getState ) => {
		const updatedPreset = {
			...getPreset( getState().preset.presets, id ),
			...preset,
		} as PresetValue;

		dispatch( { type: PRESET_SAVE, id, preset } );

		return apiFetch( SearchRegexApi.preset.update( updatedPreset ) )
			.then( ( json: any ) => {
				dispatch( { type: PRESET_SAVED, ...json } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: PRESET_SAVE_FAIL, error } );
			} );
	};

export const deletePreset =
	( id: string ): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch ) => {
		dispatch( { type: PRESET_SAVE } );

		return apiFetch( SearchRegexApi.preset.delete( id ) )
			.then( ( json: any ) => {
				dispatch( { type: PRESET_SAVED, ...json } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: PRESET_SAVE_FAIL, error } );
			} );
	};

export const setPreset = ( preset: PresetValue | null, currentOnly: boolean = false ) => ( {
	type: PRESET_SELECT,
	preset,
	currentOnly,
} );

export const clearPresetError = () => ( { type: PRESET_CLEAR } );

export const setClipboard = ( clipboard: string ) => ( { type: PRESET_SET_CLIPBOARD, clipboard } );

export const exportPresets = (): ThunkAction< void, RootState, unknown, any > => ( dispatch ) => {
	const request = SearchRegexApi.preset.export();

	document.location.href = apiFetch.getUrl( request.url ) + '&_wpnonce=' + SearchRegexi10n.api.WP_API_nonce;

	dispatch( { type: PRESET_CLEAR } );
};

export const uploadPreset =
	( file: File ): ThunkAction< Promise< void >, RootState, unknown, any > =>
	( dispatch ) => {
		dispatch( { type: PRESET_UPLOAD } );

		return apiFetch( SearchRegexApi.preset.upload( file ) )
			.then( ( json: any ) => {
				dispatch( { type: PRESET_UPLOAD_COMPLETE, ...json } );
			} )
			.catch( ( error: any ) => {
				dispatch( { type: PRESET_UPLOAD_FAIL, error } );
			} );
	};

export const importClipboard =
	( text: string ): ThunkAction< Promise< void > | any, RootState, unknown, any > =>
	( dispatch ) => {
		try {
			const json = JSON.parse( text );
			const presets = Array.isArray( json ) ? json : [ json ];

			return uploadPreset( new File( [ new Blob( [ JSON.stringify( presets ) ] ) ], 'preset.json' ) )(
				dispatch,
				() => ( {} ) as RootState,
				undefined
			);
		} catch ( error ) {
			return dispatch( { type: PRESET_CLIPBOARD_FAIL, error, errorContext: text } );
		}
	};

function createPreset( dispatch: Dispatch, name: string, searchValues: SearchValues ): Promise< void > {
	dispatch( { type: PRESET_SAVE } );

	return apiFetch( SearchRegexApi.preset.save( searchValues, name ) )
		.then( ( json: any ) => {
			dispatch( { type: PRESET_SAVED, ...json } );
		} )
		.catch( ( error: any ) => {
			dispatch( { type: PRESET_SAVE_FAIL, error } );
		} );
}
