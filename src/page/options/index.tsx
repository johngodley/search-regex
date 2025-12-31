import { useState, FormEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Table, TableRow, Select } from '@wp-plugin-components';
import { useSettings, useSaveSettings } from '../../hooks/use-settings';
import { usePresets } from '../../hooks/use-presets';
import type { SettingsValues } from '../../lib/api-schemas';

interface RestApiOption {
	value: number;
	label: string;
}

export const getRestApi = (): RestApiOption[] => [
	{ value: 0, label: __( 'Default REST API', 'search-regex' ) },
	{ value: 1, label: __( 'Raw REST API', 'search-regex' ) },
	{ value: 3, label: __( 'Relative REST API', 'search-regex' ) },
];

function OptionsForm() {
	const { data: values } = useSettings() as { data: SettingsValues | undefined };
	const { mutate: saveSettings, isPending } = useSaveSettings();
	const { data: presets = [] } = usePresets();

	// Single selection representing the startup behaviour – either a mode
	// (simple/advanced) or a specific preset id.
	const [ startupSelection, setStartupSelection ] = useState< string >( () => {
		if ( ! values ) {
			return 'advanced';
		}

		if ( values.startupMode === 'preset' && values.startupPreset ) {
			return values.startupPreset;
		}

		return values.startupMode;
	} );
	const [ restApi, setApi ] = useState< number >( values?.rest_api ?? 0 );

	function onSubmit( ev: FormEvent< HTMLFormElement > ) {
		ev.preventDefault();

		let startupMode: SettingsValues[ 'startupMode' ] = 'advanced';
		let startupPreset: string | undefined;

		if ( startupSelection === 'simple' || startupSelection === 'advanced' ) {
			startupMode = startupSelection;
			startupPreset = '';
		} else {
			startupMode = 'preset';
			startupPreset = startupSelection;
		}

		saveSettings( {
			startupMode,
			// Always send startupPreset so the server state stays consistent
			startupPreset,
			rest_api: restApi,
		} );
	}

	if ( ! values ) {
		return null;
	}

	return (
		<form onSubmit={ onSubmit }>
			<Table className="form-table">
				<TableRow title={ __( 'Startup Mode', 'search-regex' ) }>
					<>
						<Select
							items={ [
								{ value: 'simple', label: __( 'Simple', 'search-regex' ) },
								{ value: 'advanced', label: __( 'Advanced', 'search-regex' ) },
								...presets.map( ( preset ) => ( {
									value: String( preset.id ),
									label: preset.name,
								} ) ),
							] }
							name="startupMode"
							value={ startupSelection }
							onChange={ ( ev: React.ChangeEvent< HTMLSelectElement > ) =>
								setStartupSelection( ev.target.value )
							}
						/>{ ' ' }
						<span>
							{ __(
								'Choose which mode Search Regex should start in. You can still switch modes in the UI.',
								'search-regex'
							) }
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
				disabled={ isPending }
			/>
		</form>
	);
}

export default OptionsForm;
