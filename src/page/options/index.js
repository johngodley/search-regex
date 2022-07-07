/**
 * External dependencies
 */

import React from 'react';
import { connect } from 'react-redux';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import OptionsForm from './options-form';
import Donation from './donation';

function Options( props ) {
	const { values } = props;

	return (
		<div>
			<Donation support={ values.support } />
			<OptionsForm />
		</div>
	);
}

function mapStateToProps( state ) {
	const { values } = state.settings;

	return {
		values,
	};
}

export default connect(
	mapStateToProps,
	null
)( Options );
