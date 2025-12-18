import { __ } from '@wordpress/i18n';
import { MESSAGE_CLEAR_ERRORS, MESSAGE_CLEAR_NOTICES } from './type';
import { SETTING_LOAD_FAILED, SETTING_SAVE_FAILED, SETTING_SAVED, SETTING_SAVING } from '../settings/type';
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ROW,
	SEARCH_DELETE_COMPLETE,
	SEARCH_PERFORM_FRESH,
	SEARCH_START_FRESH,
} from '../search/type';
import {
	PRESET_SAVED,
	PRESET_SAVE,
	PRESET_SAVE_FAIL,
	PRESET_UPLOAD_FAIL,
	PRESET_UPLOAD_COMPLETE,
	PRESET_UPLOAD,
} from '../preset/type';

interface MessageState {
	errors: unknown[];
	notices: string[];
	inProgress: number;
	saving: unknown[];
}

interface MessageAction {
	type: string;
	error?: { message: string };
	rowId?: string;
}

const addErrors = ( existing: unknown[], error: unknown ): unknown[] => [ ...existing, error ];
const addNotice = ( existing: string[], notice: string ): string[] => [ ...existing, notice ];
const reduceProgress = ( state: MessageState ): number => Math.max( 0, state.inProgress - 1 );

const NOTICES: Record< string, string > = {
	SETTING_SAVED: __( 'Settings saved', 'search-regex' ),
	SEARCH_DELETE_COMPLETE: __( 'Row deleted', 'search-regex' ),
	SEARCH_REPLACE_COMPLETE: __( 'Row replaced', 'search-regex' ),
	SEARCH_SAVE_ROW_COMPLETE: __( 'Row updated', 'search-regex' ),
	PRESET_SAVED: __( 'Preset saved', 'search-regex' ),
	PRESET_UPLOAD_COMPLETE: __( 'Preset uploaded', 'search-regex' ),
};

export default function messages(
	state: MessageState = { errors: [], notices: [], inProgress: 0, saving: [] },
	action: MessageAction
): MessageState {
	switch ( action.type ) {
		case PRESET_SAVE_FAIL:
		case SEARCH_FAIL:
		case SETTING_LOAD_FAILED:
		case SETTING_SAVE_FAILED:
		case PRESET_UPLOAD_FAIL:
			/* eslint-disable */
			const errors = addErrors( state.errors, action.error );
			console.error( action.error?.message );
			/* eslint-enable */

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
