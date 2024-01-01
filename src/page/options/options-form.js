/**
 * External dependencies
 */

import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import { saveSettings } from '../../state/settings/action';
import { setPreset } from '../../state/preset/action';
import { STATUS_IN_PROGRESS } from '../../state/settings/type';
import { Table, TableRow } from '@wp-plugin-components';
import { Select } from '@wp-plugin-components';

export const getRestApi = () => [
	{ value: 0, label: __( 'Default REST API', 'search-regex' ) },
	{ value: 1, label: __( 'Raw REST API', 'search-regex' ) },
	{ value: 3, label: __( 'Relative REST API', 'search-regex' ) },
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
				<TableRow title={ __( 'Default Preset', 'search-regex' ) }>
					<label>
						<Select
							items={ [ { value: 0, label: __( 'No default preset', 'search-regex' ) } ].concat(
								presets.map( ( preset ) => ( { value: preset.id, label: preset.name } ) )
							) }
							name="defaultPreset"
							value={ defaultPreset }
							onChange={ ( ev ) => setDefaultPreset( ev.target.value ) }
						/>{' '}
						{ __( 'Set a preset to use by default when Search Regex is loaded.', 'search-regex' ) }
					</label>
				</TableRow>

				<TableRow title={ __( 'REST API', 'search-regex' ) }>
					<Select
						items={ getRestApi() }
						name="rest_api"
						onChange={ ( ev ) => setApi( parseInt( ev.target.value, 10 ) ) }
						value={ restApi }
					/>
					&nbsp;
					<span className="sub">
						{ __( "How Search Regex uses the REST API - don't change unless necessary", 'search-regex' ) }
					</span>
				</TableRow>
			</Table>

			<input
				className="button-primary"
				type="submit"
				name="update"
				value={ __( 'Update', 'search-regex' ) }
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
