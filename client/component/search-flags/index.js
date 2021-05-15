/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import { MultiOptionDropdown } from 'wp-plugin-components';
import { getAvailableSearchFlags } from 'state/search/selector';

/**
 * Search flags
 * @param {object} props Component props
 * @param {boolean} [props.allowMultiline=false] - Support multiline
 * @param {boolean} [props.allowRegex=true] - Support regex
 * @param {boolean} [props.allowCase=true] - Case
 * @param {boolean} props.disabled - Disabled
 * @param {string[]} props.flags - Current flags
 * @param {} props.onChange
 */
function SearchFlags( props ) {
	const { flags, onChange, disabled, allowRegex = true, allowMultiline = false, allowCase = true } = props;
	const options = getAvailableSearchFlags()
		.filter( ( item ) => item.value !== 'regex' || allowRegex )
		.filter( ( item ) => item.value !== 'case' || allowCase )
		.filter( ( item ) => item.value !== 'multi' || allowMultiline );

	return (
		<MultiOptionDropdown
			options={ options }
			selected={ flags }
			onApply={ ( searchFlags ) => onChange( searchFlags ) }
			title={ __( 'Flags' ) }
			disabled={ disabled }
			multiple
			badges
		/>
	);
}

export default SearchFlags;
