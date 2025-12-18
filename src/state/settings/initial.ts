import { STATUS_IN_PROGRESS } from './type';

declare const SearchRegexi10n: {
	preload?: {
		pluginStatus?: unknown[];
	};
	database?: Record< string, unknown >;
	settings?: Record< string, unknown >;
	api?: unknown[];
};

interface SettingsState {
	loadStatus: string;
	saveStatus: boolean | string;
	error: boolean | unknown;
	pluginStatus: unknown[];
	apiTest: Record< string, unknown >;
	database: Record< string, unknown >;
	values: Record< string, unknown >;
	api: unknown[];
	warning: boolean | unknown;
}

function getPreload(): unknown[] {
	if ( SearchRegexi10n && SearchRegexi10n.preload && SearchRegexi10n.preload.pluginStatus ) {
		return SearchRegexi10n.preload.pluginStatus;
	}

	return [];
}

export function getInitialSettings(): SettingsState {
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
