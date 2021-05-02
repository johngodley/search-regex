/**
 * External dependencies
 */

import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { MESSAGE_CLEAR_ERRORS, MESSAGE_CLEAR_NOTICES } from './type';
import { SETTING_LOAD_FAILED, SETTING_SAVE_FAILED, SETTING_SAVED, SETTING_SAVING } from 'state/settings/type';
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ROW,
	SEARCH_DELETE_COMPLETE,
	SEARCH_PERFORM_FRESH,
	SEARCH_START_FRESH,
} from 'state/search/type';
import {
	PRESET_SAVED,
	PRESET_SAVE,
	PRESET_SAVE_FAIL,
	PRESET_UPLOAD_FAIL,
	PRESET_UPLOAD_COMPLETE,
	PRESET_UPLOAD,
} from 'state/preset/type';

const addErrors = ( existing, error ) => existing.slice( 0 ).concat( [ error ] );
const addNotice = ( existing, notice ) => existing.slice( 0 ).concat( [ notice ] );
const reduceProgress = ( state ) => Math.max( 0, state.inProgress - 1 );

const NOTICES = {
	SETTING_SAVED: __( 'Settings saved' ),
	SEARCH_DELETE_COMPLETE: __( 'Row deleted' ),
	SEARCH_REPLACE_COMPLETE: __( 'Row replaced' ),
	SEARCH_SAVE_ROW_COMPLETE: __( 'Row updated' ),
	PRESET_SAVED: __( 'Preset saved' ),
};

export default function messages( state = {}, action ) {
	switch ( action.type ) {
		case PRESET_SAVE_FAIL:
		case SEARCH_FAIL:
		case SETTING_LOAD_FAILED:
		case SETTING_SAVE_FAILED:
		case PRESET_UPLOAD_FAIL:
			/* eslint-disable */
			const errors = addErrors( state.errors, action.error );
			console.error( action.error.message );
			/* eslint-disable */

			return { ...state, errors, inProgress: reduceProgress( state ) };

		case PRESET_UPLOAD:
		case PRESET_SAVE:
		case SEARCH_REPLACE_ROW:
		case SETTING_SAVING:
			return { ...state, inProgress: state.inProgress + 1 };

		case PRESET_UPLOAD_COMPLETE:
		case PRESET_SAVED:
		case SEARCH_DELETE_COMPLETE:
		case SETTING_SAVED:
			return {
				...state,
				notices: addNotice( state.notices, NOTICES[ action.type ] ),
				inProgress: reduceProgress( state ),
			};

		case MESSAGE_CLEAR_NOTICES:
			return { ...state, notices: [] };

		case SEARCH_START_FRESH:
		case SEARCH_PERFORM_FRESH:
		case MESSAGE_CLEAR_ERRORS:
			return { ...state, errors: [] };
	}

	return state;
}
