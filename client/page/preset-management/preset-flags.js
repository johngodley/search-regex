/**
 * External dependencies
 */

import React from 'react';
import { connect } from 'react-redux';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */
import { getAvailableSearchFlags } from 'state/search/selector';
import { getSource } from 'lib/sources';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

/**
 * Display all flags for a preset
 *
 * @param {object} props - Component props
 * @param {PresetValue} props.preset - Preset
 * @param {SearchSourceGroup[]} [props.sources] - Sources
 */
function PresetFlags( props ) {
	const { sources, preset } = props;
	const { search, locked } = preset;
	const flags = [];
	const { searchFlags, source } = search;

	// Add sources
	for ( let index = 0; index < source.length; index++ ) {
		const flag = getSource( sources, source[ index ] );

		if ( flag ) {
			flags.push( flag.label );
		}
	}

	// Add non-default search flags
	for ( let index = 0; index < searchFlags.length; index++ ) {
		const flag = getAvailableSearchFlags().find(
			/** @param {{value: string}} flag */ ( flag ) => flag.value === searchFlags[ index ]
		);

		if ( flag && searchFlags[ index ] !== 'case' ) {
			flags.push( flag.label );
		}
	}

	if ( locked.length > 0 ) {
		flags.push( __( 'Locked fields' ) );
	}

	return <p>{ flags.join( ', ' ) }</p>;
}

function mapStateToProps( state ) {
	const { sources } = state.search;

	return {
		sources,
	};
}

export default connect(
	mapStateToProps,
	null
)( PresetFlags );
