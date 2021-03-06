/* global SearchRegexi10n */
/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

import { ExternalLink, Notice } from 'wp-plugin-components';

const Help = () => {
	return (
		<div>
			<h2>{ __( 'Need more help?' ) }</h2>
			<p>
				{ __(
					'Full documentation for Search Regex can be found at {{site}}https://searchregex.com{{/site}}.',
					{
						components: {
							site: <ExternalLink url="https://searchregex.com/support/" />,
						},
					}
				) }
			</p>
			<p>
				<strong>
					{ __(
						'If you want to report a bug please read the {{report}}Reporting Bugs{{/report}} guide.',
						{
							components: {
								report: <ExternalLink url="https://searchregex.com/support/reporting-bugs/" />,
							},
						}
					) }
				</strong>
			</p>

			<Notice level="general">
				<p className="github">
					<ExternalLink url="https://github.com/johngodley/search-regex/issues">
						<img
							src={ SearchRegexi10n.pluginBaseUrl + '/images/GitHub-Mark-64px.png' }
							width="32"
							height="32"
						/>
					</ExternalLink>
					<ExternalLink url="https://github.com/johngodley/search-regex/issues">
						https://github.com/johngodley/search-regex/
					</ExternalLink>
				</p>
			</Notice>
			<p>
				{ __(
					'Please note that any support is provide on as-time-is-available basis and is not guaranteed. I do not provide paid support.'
				) }
			</p>
			<p>
				{ __(
					"If you want to submit information that you don't want in a public repository then send it directly via {{email}}email{{/email}} - include as much information as you can!",
					{
						components: {
							email: (
								<a
									href={
										'mailto:john@searchregex.com?subject=Search%20Regex%20Issue&body=' +
										encodeURIComponent( 'Search Regex: ' + SearchRegexi10n.versions )
									}
								/>
							),
						},
					}
				) }
			</p>
			<h2>{ __( 'Redirection' ) }</h2>
			<p>
				{ __(
					'Like this plugin? You might want to consider {{link}}Redirection{{/link}}, a plugin to manage redirects, from the same author.',
					{
						components: {
							link: <ExternalLink url="https://redirection.me" />,
						},
					}
				) }
			</p>
		</div>
	);
};

export default Help;
