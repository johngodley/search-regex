import React, { useState, FormEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import { saveSettings } from '../../state/settings/action';
import { setPreset } from '../../state/preset/action';
import { STATUS_IN_PROGRESS } from '../../state/settings/type';
import { Table, TableRow, Select } from '@wp-plugin-components';
import type { PresetValue } from '../../types/preset';

interface RootState {
	settings: {
		values: SettingsValues;
		saveStatus: string | false;
	};
	preset: {
		presets: PresetValue[];
	};
}

interface SettingsValues {
	support: boolean;
	defaultPreset: number;
	rest_api: number;
}

interface RestApiOption {
	value: number;
	label: string;
}

export const getRestApi = (): RestApiOption[] => [
	{ value: 0, label: __( 'Default REST API', 'search-regex' ) },
	{ value: 1, label: __( 'Raw REST API', 'search-regex' ) },
	{ value: 3, label: __( 'Relative REST API', 'search-regex' ) },
];

const mapStateToProps = ( state: RootState ) => {
	const { values, saveStatus } = state.settings;
	const { presets } = state.preset;

	return {
		values,
		saveStatus,
		presets,
	};
};

/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
const mapDispatchToProps = ( dispatch: any ) => {
	return {
		onSaveSettings: ( settings: Partial< SettingsValues >, defaultPreset?: PresetValue ) => {
			dispatch( saveSettings( settings ) );

			if ( defaultPreset ) {
				dispatch( setPreset( defaultPreset, true ) );
			}
		},
	};
};
/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */

type StateProps = ReturnType< typeof mapStateToProps >;
type DispatchProps = ReturnType< typeof mapDispatchToProps >;
type OptionsFormProps = StateProps & DispatchProps;

function OptionsForm( props: OptionsFormProps ) {
	const { saveStatus, values, onSaveSettings, presets } = props;
	const [ defaultPreset, setDefaultPreset ] = useState< number >( values.defaultPreset );
	const [ restApi, setApi ] = useState< number >( values.rest_api );

	function onSubmit( ev: FormEvent< HTMLFormElement > ) {
		ev.preventDefault();

		onSaveSettings(
			{
				defaultPreset,
				rest_api: restApi,
			},
			presets.find( ( item ) => item.id === String( defaultPreset ) )
		);
	}

	return (
		<form onSubmit={ onSubmit }>
			<Table className="form-table">
				<TableRow title={ __( 'Default Preset', 'search-regex' ) }>
					<>
						<Select
							items={ [
								{ value: '0', label: __( 'No default preset', 'search-regex' ) },
								...presets.map( ( preset ) => ( { value: String( preset.id ), label: preset.name } ) ),
							] }
							name="defaultPreset"
							value={ String( defaultPreset ) }
							onChange={ ( ev: React.ChangeEvent< HTMLSelectElement > ) =>
								setDefaultPreset( Number( ev.target.value ) )
							}
						/>{ ' ' }
						<span>
							{ __( 'Set a preset to use by default when Search Regex is loaded.', 'search-regex' ) }
						</span>
					</>
				</TableRow>

				<TableRow title={ __( 'REST API', 'search-regex' ) }>
					<Select
						items={ getRestApi().map( ( item ) => ( { value: String( item.value ), label: item.label } ) ) }
						name="rest_api"
						onChange={ ( ev: React.ChangeEvent< HTMLSelectElement > ) =>
							setApi( parseInt( ev.target.value, 10 ) )
						}
						value={ String( restApi ) }
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

export default connect( mapStateToProps, mapDispatchToProps )( OptionsForm );
