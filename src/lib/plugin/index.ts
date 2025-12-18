import { getPageUrl } from '@wp-plugin-lib';

const ALLOWED_PAGES = [ 'search', 'options', 'support', 'presets' ] as const;
type AllowedPage = ( typeof ALLOWED_PAGES )[ number ];

export function getPluginPage( url = '' ): AllowedPage {
	const params = getPageUrl( url ) as { sub?: string };

	if ( params.sub && ALLOWED_PAGES.indexOf( params.sub as AllowedPage ) !== -1 ) {
		return params.sub as AllowedPage;
	}

	return ALLOWED_PAGES[ 0 ];
}
