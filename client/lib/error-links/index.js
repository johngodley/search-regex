export function getErrorLinks() {
	return {
		url: 'https://searchregex.com/support/problems/rest-api/#url',
		http: 'https://searchregex.com/support/problems/rest-api/#http',
		api: 'https://searchregex.com/support/problems/rest-api/',
		rootUrl: SearchRegexi10n.api.WP_API_root,
		siteHealth: SearchRegexi10n.api.site_health,
	};
}

export function getErrorDetails() {
	return SearchRegexi10n.versions.split( '\n' ).concat( [ 'Query: ' + document.location.search ] );
}

export function getCacheBuster() {
	return 'Buster: ' + SEARCHREGEX_VERSION + ' === ' + SearchRegexi10n.version;
}
