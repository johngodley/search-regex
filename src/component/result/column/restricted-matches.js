/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

function RestrictedMatches() {
	return (
		<div className="searchregex-result__more">
			{ __( 'Maximum number of matches exceeded and hidden from view. These will be included in any replacements.' ) }
		</div>
	);
}

export default RestrictedMatches;
