/**
 * External dependencies
 */

import React from 'react';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import { loadSettings } from 'state/settings/action';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE } from 'state/settings/type';
import OptionsForm from './options-form';
import Placeholder from 'component/placeholder';
import Donation from './donation';

class Options extends React.Component {
	componentDidMount() {
		this.props.onLoadSettings();
	}

	render() {
		const { loadStatus, values } = this.props;

		if ( loadStatus === STATUS_IN_PROGRESS || ! values ) {
			return <Placeholder />;
		}

		return (
			<div>
				{ loadStatus === STATUS_COMPLETE && <Donation support={ values.support } /> }
				{ loadStatus === STATUS_COMPLETE && <OptionsForm /> }
			</div>
		);
	}
}

function mapDispatchToProps( dispatch ) {
	return {
		onLoadSettings: () => {
			dispatch( loadSettings() );
		},
	};
}

function mapStateToProps( state ) {
	const { loadStatus, values } = state.settings;

	return {
		loadStatus,
		values,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Options );
