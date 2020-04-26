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
	SETTING_LOAD_STATUS,
	SETTING_API_SUCCESS,
	SETTING_API_FAILED,
	SETTING_API_TRY,

	STATUS_IN_PROGRESS,
	STATUS_COMPLETE,
	STATUS_FAILED,
} from './type';

const DB_STATUS_OK = 'ok';
const DB_STATUS_LOADING = 'loading';
const DB_STATUS_FAIL = 'fail';

function setApiTest( existing, id, method, result ) {
	const api = existing[ id ] ? { ... existing[ id ] } : [];

	api[ method ] = result;

	return { [ id ]: api };
}

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case SETTING_API_TRY:
			return { ... state, apiTest: { ... state.apiTest, ... setApiTest( state.apiTest, action.id, action.method, { status: DB_STATUS_LOADING } ) } };

		case SETTING_API_SUCCESS:
			return { ... state, apiTest: { ... state.apiTest, ... setApiTest( state.apiTest, action.id, action.method, { status: DB_STATUS_OK } ) } };

		case SETTING_API_FAILED:
			return { ... state, apiTest: { ... state.apiTest, ... setApiTest( state.apiTest, action.id, action.method, { status: DB_STATUS_FAIL, error: action.error } ) } };

		case SETTING_LOAD_START:
			return { ... state, loadStatus: STATUS_IN_PROGRESS };

		case SETTING_LOAD_SUCCESS:
			return { ... state, loadStatus: STATUS_COMPLETE, values: action.values };

		case SETTING_LOAD_FAILED:
			return { ... state, loadStatus: STATUS_FAILED, error: action.error };

		case SETTING_SAVING:
			return { ... state, saveStatus: STATUS_IN_PROGRESS, warning: false };

		case SETTING_SAVED:
			return { ... state, saveStatus: STATUS_COMPLETE, values: action.values,  warning: action.warning ? action.warning : false };

		case SETTING_SAVE_FAILED:
			return { ... state, saveStatus: STATUS_FAILED, error: action.error };

		case SETTING_LOAD_STATUS:
			return { ... state, pluginStatus: action.pluginStatus };
	}

	return state;
}
