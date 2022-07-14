interface API {
	WP_API_root: string;
	WP_API_nonce: string;
	routes: object;
}

interface SearchRegexi10n {
	pluginRoot: string;
	locale: string;
	versions: string;
	pluginBaseUrl: string;
	api: API;
	version: string;
	update_notice: string;
}

declare global {
	interface Window {
		SearchRegexi10n: SearchRegexi10n;
	}
}

export {}
