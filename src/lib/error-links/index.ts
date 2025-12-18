type SearchRegexApiInfo = {
	WP_API_root: string;
	site_health: string;
};

type SearchRegexContext = {
	api: SearchRegexApiInfo;
	versions: string;
	version: string;
};

declare const SearchRegexi10n: SearchRegexContext;
declare const SEARCHREGEX_VERSION: string;

export function getErrorLinks() {
	return {
		url: 'https://searchregex.com/support/problems/rest-api/#url',
		http: 'https://searchregex.com/support/problems/rest-api/#http',
		api: 'https://searchregex.com/support/problems/rest-api/',
		rootUrl: SearchRegexi10n.api.WP_API_root,
		siteHealth: SearchRegexi10n.api.site_health,
	};
}

export function getErrorDetails(): string[] {
	return SearchRegexi10n.versions.split( '\n' ).concat( [ 'Query: ' + document.location.search ] );
}

export function getCacheBuster(): string {
	return 'Buster: ' + SEARCHREGEX_VERSION + ' === ' + SearchRegexi10n.version;
}
