declare module 'qs';
declare module 'deep-equal';
declare module 'react-copy-to-clipboard';

declare global {
	const SEARCHREGEX_VERSION: string;

	interface SearchRegexi10nType {
		version: string;
		locale: string;
		pluginRoot: string;
		update_notice?: string | false;
		settings: {
			defaultPreset?: string;
			[key: string]: unknown;
		};
		[key: string]: unknown;
	}

	const SearchRegexi10n: SearchRegexi10nType;

	interface Window {
		SearchRegexi10n: SearchRegexi10nType;
	}
}

export {};
