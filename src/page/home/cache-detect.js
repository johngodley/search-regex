/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ExternalLink, Error } from '@wp-plugin-components';
import { getErrorLinks, getErrorDetails, getCacheBuster } from '../../lib/error-links';

function CacheDetect() {
	return (
		<Error
			errors={ '' }
			details={ getErrorDetails().concat( [ getCacheBuster() ] ) }
			type="fixed"
			title={ __( 'Cached Search Regex detected' ) }
			links={ getErrorLinks() }
		>
			<p>{ __( 'Please clear your browser cache and reload this page.' ) }</p>
			<p>
				{ __( 'If you are using a caching system such as Cloudflare then please read this: ' ) }
				<ExternalLink url="https://searchregex.com/support/problems/cloudflare/">
					{ __( 'clearing your cache.' ) }
				</ExternalLink>
			</p>
		</Error>
	);
}

export default CacheDetect;
