/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */

import { ExternalLink } from 'wp-plugin-components';
import RestApiStatus from 'component/rest-api-status';

function ErrorDetails() {
	return (
		<>
			<RestApiStatus />

			<h3>{ __( 'What do I do next?' ) }</h3>

			<ol>
				<li>
					{ __(
						'Take a look at the {{link}}plugin status{{/link}}. It may be able to identify and "magic fix" the problem.',
						{
							components: {
								link: <a href="?page=search-regex.php&sub=support" />,
							},
						}
					) }
				</li>
				<li>
					{ __(
						'{{link}}Caching software{{/link}}, in particular Cloudflare, can cache the wrong thing. Try clearing all your caches.',
						{
							components: {
								link: <ExternalLink url="https://searchregex.com/support/problems/cloudflare/" />,
							},
						}
					) }
				</li>
				<li>
					{ __( '{{link}}Please temporarily disable other plugins!{{/link}} This fixes so many problems.', {
						components: {
							link: <ExternalLink url="https://searchregex.com/support/problems/plugins/" />,
						},
					} ) }
				</li>
				<li>
					{ __(
						'If you are using WordPress 5.2 or newer then look at your {{link}}Site Health{{/link}} and resolve any issues.',
						{
							components: {
								link: <ExternalLink url="/wp-admin/site-health.php" />,
							},
						}
					) }
				</li>
			</ol>
		</>
	);
}

export default ErrorDetails;
