/**
 * External dependencies
 */
import { combineReducers } from 'redux';

import settings from 'state/settings/reducer';
import search from 'state/search/reducer';
import message from 'state/message/reducer';

const reducer = combineReducers( {
	settings,
	search,
	message,
} );

export default reducer;
