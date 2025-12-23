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
	const [ defaultPreset, setDefaultPreset ] = useState< string >( values?.defaultPreset ?? '0' );
	const [ restApi, setApi ] = useState< number >( values?.rest_api ?? 0 );

	function onSubmit( ev: FormEvent< HTMLFormElement > ) {
		ev.preventDefault();

		saveSettings( {
			defaultPreset,
			rest_api: restApi,
		} );
	}

	if ( ! values ) {
		return null;
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
							value={ defaultPreset }
							onChange={ ( ev: React.ChangeEvent< HTMLSelectElement > ) =>
								setDefaultPreset( ev.target.value )
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
				disabled={ isPending }
			/>
		</form>
	);
}

export default OptionsForm;
