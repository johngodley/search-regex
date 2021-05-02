/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __ } from 'i18n-calypso';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import { saveSettings } from 'state/settings/action';
import { setPreset } from 'state/preset/action';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { Table, TableRow } from 'wp-plugin-components';
import { Select } from 'wp-plugin-components';

export const getRestApi = () => [
	{ value: 0, label: __( 'Default REST API' ) },
	{ value: 1, label: __( 'Raw REST API' ) },
	{ value: 3, label: __( 'Relative REST API' ) },
];

function OptionsForm( props ) {
	const { saveStatus, values, onSaveSettings, presets } = props;
	const [ support, setSupport ] = useState( values.support );
	const [ defaultPreset, setDefaultPreset ] = useState( values.defaultPreset );
	const [ restApi, setApi ] = useState( values.rest_api );

	function onSubmit( ev ) {
		ev.preventDefault();

		onSaveSettings(
			{
				support,
				defaultPreset,
				rest_api: restApi,
			},
			presets.find( ( item ) => item.id === defaultPreset )
		);
	}

	return (
		<form onSubmit={ onSubmit }>
			<Table className="form-table">
				<TableRow title="">
					<label>
						<input
							type="checkbox"
							checked={ support }
							name="support"
							onChange={ ( ev ) => setSupport( ev.target.checked ) }
						/>
						<span className="sub">
							{ __( "I'm a nice person and I have helped support the author of this plugin" ) }
						</span>
					</label>
				</TableRow>

				<TableRow title={ __( 'Default Preset' ) }>
					<label>
						<Select
							items={ [ { value: 0, label: __( 'No default preset' ) } ].concat(
								presets.map( ( preset ) => ( { value: preset.id, label: preset.name } ) )
							) }
							name="defaultPreset"
							value={ defaultPreset }
							onChange={ ( ev ) => setDefaultPreset( ev.target.value ) }
						/>{' '}
						{ __( 'Set a preset to use by default when Search Regex is loaded.' ) }
					</label>
				</TableRow>

				<TableRow title={ __( 'REST API' ) }>
					<Select
						items={ getRestApi() }
						name="rest_api"
						onChange={ ( ev ) => setApi( parseInt( ev.target.value, 10 ) ) }
						value={ restApi }
					/>
					&nbsp;
					<span className="sub">
						{ __( "How Search Regex uses the REST API - don't change unless necessary" ) }
					</span>
				</TableRow>
			</Table>

			<input
				className="button-primary"
				type="submit"
				name="update"
				value={ __( 'Update' ) }
				disabled={ saveStatus === STATUS_IN_PROGRESS }
			/>
		</form>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onSaveSettings: ( settings, defaultPreset ) => {
			dispatch( saveSettings( settings ) );

			if ( defaultPreset ) {
				dispatch( setPreset( defaultPreset, true ) );
			}
		},
	};
}

function mapStateToProps( state ) {
	const { values, saveStatus } = state.settings;
	const { presets } = state.preset;

	return {
		values,
		saveStatus,
		presets,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( OptionsForm );
