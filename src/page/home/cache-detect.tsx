import { __ } from '@wordpress/i18n';
import { ExternalLink, Error } from '@wp-plugin-components';
import { getErrorLinks, getErrorDetails, getCacheBuster } from '../../lib/error-links';

function CacheDetect() {
	return (
		<Error
			errors={ '' }
			details={ getErrorDetails().concat( [ getCacheBuster() ] ) }
			type="fixed"
			title={ __( 'Cached Search Regex detected', 'search-regex' ) }
			links={ getErrorLinks() }
			locale={ SearchRegexi10n.locale }
		>
			<p>{ __( 'Please clear your browser cache and reload this page.', 'search-regex' ) }</p>
			<p>
				{ __( 'If you are using a caching system such as Cloudflare then please read this:', 'search-regex' ) }{ ' ' }
				<ExternalLink url="https://searchregex.com/support/problems/cloudflare/">
					{ __( 'clearing your cache.', 'search-regex' ) }
				</ExternalLink>
			</p>
		</Error>
	);
}

export default CacheDetect;
