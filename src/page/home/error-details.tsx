import { __ } from '@wordpress/i18n';
import { ExternalLink, createInterpolateElement } from '@wp-plugin-components';
import RestApiStatus from '../../component/rest-api-status';

export default function ErrorDetails() {
	return (
		<>
			<RestApiStatus />

			<h3>{ __( 'What do I do next?', 'search-regex' ) }</h3>

			<ol>
				<li>
					{ createInterpolateElement(
						__(
							'{{link}}Caching software{{/link}}, in particular Cloudflare, can cache the wrong thing. Try clearing all your caches.',
							'search-regex'
						),
						{
							link: <ExternalLink url="https://searchregex.com/support/problems/cloudflare/" />,
						}
					) }
				</li>
				<li>
					{ createInterpolateElement(
						__(
							'{{link}}Please temporarily disable other plugins!{{/link}} This fixes so many problems.',
							'search-regex'
						),
						{
							link: <ExternalLink url="https://searchregex.com/support/problems/plugins/" />,
						}
					) }
				</li>
				<li>
					{ createInterpolateElement(
						__( 'Look at your {{link}}Site Health{{/link}} and resolve any issues.', 'search-regex' ),
						{
							link: <ExternalLink url="/wp-admin/site-health.php" />,
						}
					) }
				</li>
			</ol>
		</>
	);
}
