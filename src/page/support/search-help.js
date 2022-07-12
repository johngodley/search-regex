/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { ExternalLink, createInterpolateElement } from '@wp-plugin-components';

function SearchHelp() {
	return (
		<div className="searchregex-help">
			<h3>{ __( 'Quick Help', 'search-regex' ) }</h3>

			<p>{ __( 'The following concepts are used by Search Regex:', 'search-regex' ) }</p>

			<ul>
				<li>
					{ createInterpolateElement(
						__( '{{link}}Search Flags{{/link}} - additional qualifiers for your search, to enable case insensitivity, and to enable regular expression support.', 'search-regex' ),
						{
							link: <ExternalLink url="https://searchregex.com/support/searching/" />,
						},
					) }
				</li>
				<li>
					{ createInterpolateElement(
						__( '{{link}}Regular expression{{/link}} - a way of defining a pattern for text matching. Provides more advanced matches.', 'search-regex' ),
						{
							link: <ExternalLink url="https://searchregex.com/support/regular-expression/" />,
						},
					) }
				</li>
				<li>
					{ createInterpolateElement(
						__( '{{link}}Source{{/link}} - the source of data you wish to search. For example, posts, pages, or comments.', 'search-regex' ),
						{
							link: <ExternalLink url="https://searchregex.com/support/search-source/" />,
						},
					) }
				</li>
				<li>
					{ createInterpolateElement(
						__( '{{link}}Source Flags{{/link}} - additional options for the selected source. For example, include post {{guid}}GUID{{/guid}} in the search.', 'search-regex' ),
						{
							link: <ExternalLink url="https://searchregex.com/support/search-source/" />,
							guid: <ExternalLink url="https://deliciousbrains.com/wordpress-post-guids-sometimes-update/" />
						}
					) }
				</li>
			</ul>
		</div>
	)
}

export default SearchHelp;
