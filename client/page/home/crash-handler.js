/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { ExternalLink, Error } from 'wp-plugin-components';
import DebugReport from './debug';

function CrashHandler( stack, errorInfo, extra ) {
	const debug = [
		SearchRegexi10n.versions,
		'Buster: ' + SEARCHREGEX_VERSION + ' === ' + SearchRegexi10n.version,
		'',
		stack ? stack : '',
	];

	if ( errorInfo ) {
		debug.push( errorInfo.componentStack );
	}

	if ( SEARCHREGEX_VERSION !== SearchRegexi10n.version ) {
		return (
			<Error
				errors={ debug }
				versions={ SearchRegexi10n.versions }
				renderDebug={ DebugReport }
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

	return (
		<Error errors={ debug } versions={ SearchRegexi10n.versions } renderDebug={ DebugReport } type="fixed">
			<p>
				{ __( 'Search Regex is not working. Try clearing your browser cache and reloading this page.' ) } &nbsp;
				{ __(
					'If you are using a page caching plugin or service (CloudFlare, OVH, etc) then you can also try clearing that cache.'
				) }
			</p>

			<p>
				{ __(
					"If that doesn't help, open your browser's error console and create a {{link}}new issue{{/link}} with the details.",
					{
						components: {
							link: <ExternalLink url="https://github.com/johngodley/searchregex/issues" />,
						},
					}
				) }
			</p>
			<p>
				{ __( 'Please mention {{code}}%s{{/code}}, and explain what you were doing at the time.', {
					components: {
						code: <code />,
					},
					args: extra?.page,
				} ) }
			</p>
		</Error>
	);
}

export default CrashHandler;
