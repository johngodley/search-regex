/**
 * External dependencies
 */

import { translate as __ } from 'wp-plugin-library/lib/locale';

/**
 * Internal dependencies
 */
import { MESSAGE_CLEAR_ERRORS, MESSAGE_CLEAR_NOTICES } from './type';
import {
	SETTING_LOAD_FAILED,
	SETTING_SAVE_FAILED,
	SETTING_SAVED,
	SETTING_SAVING,
} from 'state/settings/type';
import {
	SEARCH_FAIL,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_DELETE_COMPLETE,
	SEARCH_SAVE_ROW_COMPLETE,
} from 'state/search/type';

const addErrors = ( existing, error ) => existing.slice( 0 ).concat( [ error ] );
const addNotice = ( existing, notice ) => existing.slice( 0 ).concat( [ notice ] );
const reduceProgress = state => Math.max( 0, state.inProgress - 1 );

const NOTICES = {
	SETTING_SAVED: __( 'Settings saved' ),
	SEARCH_DELETE_COMPLETE: __( 'Row deleted' ),
	SEARCH_REPLACE_COMPLETE: __( 'Row replaced' ),
	SEARCH_SAVE_ROW_COMPLETE: __( 'Row updated' ),
};

export default function messages( state = {}, action ) {
	switch ( action.type ) {
		case SEARCH_FAIL:
		case SETTING_LOAD_FAILED:
		case SETTING_SAVE_FAILED:
			/* eslint-disable */
			const errors = addErrors( state.errors, action.error );
			console.error( action.error.message );
			/* eslint-disable */

			return { ... state, errors, inProgress: reduceProgress( state ) };

		case SEARCH_REPLACE_ROW:
		case SETTING_SAVING:
			return { ... state, inProgress: state.inProgress + 1 };

		case SEARCH_REPLACE_COMPLETE:
		case SEARCH_DELETE_COMPLETE:
		case SEARCH_SAVE_ROW_COMPLETE:
		case SETTING_SAVED:
			return { ... state, notices: addNotice( state.notices, NOTICES[ action.type ] ), inProgress: reduceProgress( state ) };

		case MESSAGE_CLEAR_NOTICES:
			return { ... state, notices: [] };

		case MESSAGE_CLEAR_ERRORS:
			return { ... state, errors: [] };
	}

	return state;
}
