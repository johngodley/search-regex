/**
 * Internal dependencies
 */

import { getInitialSettings } from 'state/settings/initial';
import { getInitialSearch } from 'state/search/initial';
import { getInitialMessage } from 'state/message/initial';
import { getInitialPreset } from 'state/preset/initial';

export function initialActions( store ) {
	return store;
}

export function getInitialState() {
	return {
		settings: getInitialSettings(),
		search: getInitialSearch(),
		message: getInitialMessage(),
		preset: getInitialPreset(),
	};
}
