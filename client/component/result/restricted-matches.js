/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

function RestrictedMatches() {
	return (
		<div className="searchregex-result__more">
			{ __( 'Maximum number of matches exceeded and hidden from view. These will be included in any replacements.' ) }
		</div>
	);
}

export default RestrictedMatches;
