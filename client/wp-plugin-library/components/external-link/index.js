/**
 * External dependencies
 */

import React from 'react';

/**
 * Wrap a component in an external link, with appropriate `rel` properties.
 *
 * @param {{url: string, children: object}} props - Provide the URL and child components
 */
const ExternalLink = ( { url, children } ) => {
	return <a href={ url } target="_blank" rel="noopener noreferrer">{ children }</a>;
};

export default ExternalLink;
