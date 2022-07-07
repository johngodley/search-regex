/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { ExternalLink } from '@wp-plugin-components';

/**
 * Result title
 * @param {object} props - Component props
 * @param {string} props.title - Title
 * @param {string} props.view - URL for title action
 * @returns {Element|string|object}
 */
function ResultTitle( { view, title } ) {
	const alwaysTitle = title ? title : __( 'No title' );

	if ( view ) {
		return <ExternalLink url={ view }>{ alwaysTitle }</ExternalLink>;
	}

	return alwaysTitle;
}

export default ResultTitle;
