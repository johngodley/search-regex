/**
 * External dependencies
 */

import { connect } from 'react-redux';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import OptionsForm from './options-form';

function Options( props ) {
	const { values } = props;

	return (
		<div>
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
