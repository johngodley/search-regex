/**
 * Internal dependencies
 */
import {
	SETTING_LOAD_START,
	SETTING_LOAD_SUCCESS,
	SETTING_LOAD_FAILED,
	SETTING_SAVING,
	SETTING_SAVED,
	SETTING_SAVE_FAILED,
	STATUS_COMPLETE,
	SETTING_API_SUCCESS,
	SETTING_API_FAILED,
	SETTING_API_TRY,
} from './type';
import SearchRegexApi from 'lib/api-request';
import apiFetch from 'wp-plugin-lib/api-fetch';

export const loadSettings = () => ( dispatch, getState ) => {
	if ( getState().settings.loadStatus === STATUS_COMPLETE ) {
		return null;
	}

	apiFetch( SearchRegexApi.setting.get() )
		.then( json => {
			dispatch( { type: SETTING_LOAD_SUCCESS, values: json.settings } );
		} )
		.catch( error => {
			dispatch( { type: SETTING_LOAD_FAILED, error } );
		} );

	return dispatch( { type: SETTING_LOAD_START } );
};

export const saveSettings = settings => dispatch => {
	apiFetch( SearchRegexApi.setting.update( settings ) )
		.then( json => {
			dispatch( { type: SETTING_SAVED, values: json.settings, warning: json.warning } );
		} )
		.catch( error => {
			dispatch( { type: SETTING_SAVE_FAILED, error } );
		} );

	return dispatch( { type: SETTING_SAVING, settings } );
};

export const checkApi = api => dispatch => {
	for ( let index = 0; index < api.length; index++ ) {
		const { id, url } = api[ index ];

		dispatch( { type: SETTING_API_TRY, id, method: 'GET' } );
		dispatch( { type: SETTING_API_TRY, id, method: 'POST' } );

		// Bit of a delay otherwise it can seem too fast...
		setTimeout( () => {
			// GET test
			apiFetch( SearchRegexApi.plugin.checkApi( url ) )
				.then( () => {
					dispatch( { type: SETTING_API_SUCCESS, id, method: 'GET' } );
				} )
				.catch( error => {
					dispatch( { type: SETTING_API_FAILED, id, method: 'GET', error } );
				} );

			// POST test
			apiFetch( SearchRegexApi.plugin.checkApi( url, true ) )
				.then( () => {
					dispatch( { type: SETTING_API_SUCCESS, id, method: 'POST' } );
				} )
				.catch( error => {
					dispatch( { type: SETTING_API_FAILED, id, method: 'POST', error } );
				} );
		}, 1000 );
	}
};
