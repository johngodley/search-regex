/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-library/lib/locale';

/**
 * Internal dependencies
 */

import { ExternalLink } from 'wp-plugin-library';

function SearchHelp() {
	return (
		<div className="searchregex-help">
			<h3>{ __( 'Quick Help' ) }</h3>

			<p>{ __( 'The following concepts are used by Search Regex:' ) }</p>

			<ul>
				<li>{ __( '{{link}}Search Flags{{/link}} - additional qualifiers for your search, to enable case insensitivity, and to enable regular expression support.', {
					components: {
						link: <ExternalLink url="https://searchregex.com/support/searching/" />,
					},
				} ) }</li>
				<li>{ __( '{{link}}Regular expression{{/link}} - a way of defining a pattern for text matching. Provides more advanced matches.', {
					components: {
						link: <ExternalLink url="https://searchregex.com/support/regular-expression/" />,
					},
				} ) }</li>
				<li>{ __( '{{link}}Source{{/link}} - the source of data you wish to search. For example, posts, pages, or comments.', {
					components: {
						link: <ExternalLink url="https://searchregex.com/support/search-source/" />,
					},
				} ) }</li>
				<li>{ __( '{{link}}Source Flags{{/link}} - additional options for the selected source. For example, include post {{guid}}GUID{{/guid}} in the search.', {
					components: {
						link: <ExternalLink url="https://searchregex.com/support/search-source/" />,
						guid: <ExternalLink url="https://deliciousbrains.com/wordpress-post-guids-sometimes-update/" />
					}
				} ) }</li>
			</ul>
		</div>
	)
}

export default SearchHelp;
