/**
 * Internal dependencies
 */

import { getInitialSettings } from './settings/initial';
import { getInitialSearch } from './search/initial';
import { getInitialMessage } from './message/initial';
import { getInitialPreset } from './preset/initial';

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
