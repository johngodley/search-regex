/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import { saveSettings } from 'state/settings/action';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { FormTable, TableRow } from 'component/form-table';
import Select from 'component/select';

export const restApi = () => [
	{ value: 0, label: __( 'Default REST API' ) },
	{ value: 1, label: __( 'Raw REST API' ) },
	{ value: 3, label: __( 'Relative REST API' ) },
];

class OptionsForm extends React.Component {
	constructor( props ) {
		super( props );

		this.state = props.values;
	}

	onChange = ev => {
		const { target } = ev;
		const value = target.type === 'checkbox' ? target.checked : target.value;

		this.setState( { [ target.name ]: value } );
	}

	onSubmit = ev => {
		ev.preventDefault();
		this.props.onSaveSettings( this.state );
	}

	render() {
		const { saveStatus } = this.props;

		return (
			<form onSubmit={ this.onSubmit }>
				<FormTable>
					<TableRow title="">
						<label>
							<input type="checkbox" checked={ this.state.support } name="support" onChange={ this.onChange } />
							<span className="sub">{ __( "I'm a nice person and I have helped support the author of this plugin" ) }</span>
						</label>
					</TableRow>

					<TableRow title={ __( 'Actions' ) }>
						<label>
							<input type="checkbox" checked={ this.state.actionDropdown } name="actionDropdown" onChange={ this.onChange } />
							{ __( "Show row actions as dropdown menu." ) }
						</label>
					</TableRow>

					<TableRow title={ __( 'REST API' ) }>
						<Select items={ restApi() } name="rest_api" value={ parseInt( this.state.rest_api, 10 ) } onChange={ this.onChange } /> &nbsp;
						<span className="sub">{ __( "How Search Regex uses the REST API - don't change unless necessary" ) }</span>
					</TableRow>
				</FormTable>

				<input className="button-primary" type="submit" name="update" value={ __( 'Update' ) } disabled={ saveStatus === STATUS_IN_PROGRESS } />
			</form>
		);
	}
}

function mapDispatchToProps( dispatch ) {
	return {
		onSaveSettings: settings => {
			dispatch( saveSettings( settings ) );
		},
	};
}

function mapStateToProps( state ) {
	const { values, saveStatus } = state.settings;

	return {
		values,
		saveStatus,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( OptionsForm );
