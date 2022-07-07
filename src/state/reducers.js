/**
 * External dependencies
 */
import { combineReducers } from 'redux';

import settings from '../state/settings/reducer';
import search from '../state/search/reducer';
import message from '../state/message/reducer';
import preset from '../state/preset/reducer';

const reducer = combineReducers( {
	settings,
	search,
	message,
	preset,
} );

export default reducer;
