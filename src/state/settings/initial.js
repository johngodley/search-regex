/* global SearchRegexi10n */
/**
 * Internal dependencies
 */

import { STATUS_IN_PROGRESS } from './type';

function getPreload() {
	if ( SearchRegexi10n && SearchRegexi10n.preload && SearchRegexi10n.preload.pluginStatus ) {
		return SearchRegexi10n.preload.pluginStatus;
	}

	return [];
}

export function getInitialSettings() {
	const pluginStatus = getPreload();

	return {
		loadStatus: STATUS_IN_PROGRESS,
		saveStatus: false,
		error: false,
		pluginStatus,
		apiTest: {},
		database: SearchRegexi10n.database ? SearchRegexi10n.database : {},
		values: SearchRegexi10n.settings ? SearchRegexi10n.settings : {},
		api: SearchRegexi10n.api ? SearchRegexi10n.api : [],
		warning: false,
	};
}
