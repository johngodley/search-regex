/**
 * Settings values structure
 * Imported from api-schemas.ts for consistency with Zod validation
 */
import type { SettingsValues } from '../lib/api-schemas';

export type { SettingsValues };

/**
 * Complete type definition for the global SearchRegexi10n object
 * This object is provided by WordPress via wp_localize_script
 */
export interface SearchRegexi10n {
	api: {
		WP_API_root: string;
		WP_API_nonce: string;
		site_health: string;
		current: number;
		routes: Record< string, string >;
	};
	pluginBaseUrl: string;
	pluginRoot: string;
	locale: string;
	settings?: SettingsValues;
	preload?: Record< string, unknown >;
	versions: string;
	version: string;
	caps: {
		pages: string[];
		capabilities: string[];
	};
	update_notice?: string | false;
}

/**
 * Global declaration for SearchRegexi10n
 * This is injected by WordPress via wp_localize_script
 */
declare global {
	// eslint-disable-next-line no-var
	var SearchRegexi10n: SearchRegexi10n;
	// eslint-disable-next-line no-var
	var SEARCHREGEX_VERSION: string;
}

export {};
