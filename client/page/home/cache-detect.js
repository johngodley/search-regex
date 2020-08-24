/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { ExternalLink, Error } from 'wp-plugin-components';

function CacheDetect() {
	return (
		<Error
			errors={ [
				SearchRegexi10n.versions + '\nServer: ' + SearchRegexi10n.version + ' !== ' + SEARCHREGEX_VERSION,
			] }
			versions={ SearchRegexi10n.versions }
			type="fixed"
			title={ __( 'Cached Search Regex detected' ) }
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
