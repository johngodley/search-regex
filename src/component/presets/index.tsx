import { useState, type FormEvent, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import './style.scss';
import { Select, DropdownMenu, Modal } from '@wp-plugin-components';
import { STATUS_IN_PROGRESS } from '../../lib/constants';
import { usePresets, useSavePreset, useUpdatePreset } from '../../hooks/use-presets';
import { usePresetStore } from '../../stores/preset-store';
import { useSearchStore } from '../../stores/search-store';
import type { PresetValue } from '../../types/preset';
import type { SearchValues } from '../../types/search';

function Presets(): JSX.Element {
	const { data: presets = [], isLoading: presetsLoading } = usePresets();

	const currentPreset = usePresetStore( ( state ) => state.currentPreset );
	const setCurrentPreset = usePresetStore( ( state ) => state.setCurrentPreset );
	const search = useSearchStore( ( state ) => state.search );
	const searchStatus = useSearchStore( ( state ) => state.status );
	const savePresetMutation = useSavePreset();
	const updatePresetMutation = useUpdatePreset();
	const [ askName, showName ] = useState( false );
	const [ presetName, setPresetName ] = useState( '' );
	const status = presetsLoading ? STATUS_IN_PROGRESS : '';

	const onChangePreset = ( preset: PresetValue | undefined ) => {
		setCurrentPreset( preset ?? null );
	};

	const onSavePreset = ( name: string, searchValues: SearchValues ) => {
		savePresetMutation.mutate( { name, searchValues } );
	};

	const onUpdatePreset = ( id: string, searchValues: { search: SearchValues } ) => {
		const presetToUpdate = presets.find( ( p ) => p.id === id );
		if ( presetToUpdate ) {
			updatePresetMutation.mutate( { ...presetToUpdate, ...searchValues } );
		}
	};

	const savingItem = [
		{
			label: __( 'Saving preset', 'search-regex' ),
			value: '',
		},
	];
	const items = [
		{ label: __( 'No preset', 'search-regex' ), value: '' },
		...presets.map( ( item ) => ( {
			label: item.name.substring( 0, 50 ),
			value: item.id,
		} ) ),
	];
	const askPresetName = ( ev: FormEvent ): void => {
		ev.preventDefault();
		showName( true );
		setPresetName( '' );
	};
	const savePresetHandler = ( ev: FormEvent ): void => {
		ev.preventDefault();
		ev.stopPropagation();
		onSavePreset( presetName, search );
		showName( false );
		setPresetName( '' );
	};
	const changePreset = ( ev: ChangeEvent< HTMLSelectElement > ): void => {
		const preset = presets.find( ( item ) => item.id === ev.target.value );

		onChangePreset( preset );
	};
	const updatePresetHandler = ( ev: FormEvent ): void => {
		ev.preventDefault();
		if ( currentPreset && currentPreset.id ) {
			onUpdatePreset( currentPreset.id, { search } );
		}
	};

	const presetActions: JSX.Element[] = [];

	if ( ! currentPreset || ! currentPreset.id ) {
		presetActions.push(
			<button type="button" onClick={ askPresetName } key="save-new">
				{ __( 'Save search as new preset', 'search-regex' ) }
			</button>
		);
	}

	if ( currentPreset && currentPreset.id ) {
		presetActions.push(
			<button type="button" onClick={ updatePresetHandler } key="update">
				{ __( 'Update current preset', 'search-regex' ) }
			</button>
		);
	}

	return (
		<div className="searchregex-saved">
			{ askName && (
				<Modal onClose={ () => showName( false ) } className="searchregex-preset__name">
					<h2>{ __( 'Saving Preset', 'search-regex' ) }</h2>
					<p>{ __( 'Enter a name for your preset', 'search-regex' ) }</p>
					<form onSubmit={ savePresetHandler }>
						<input
							type="text"
							name="name"
							value={ presetName }
							onChange={ ( ev ) => setPresetName( ev.target.value ) }
							placeholder={ __( 'Enter preset name', 'search-regex' ) }
						/>
						<button className="button button-primary" disabled={ presetName.length === 0 }>
							{ __( 'Save', 'search-regex' ) }
						</button>

						<button className="button button-secondary" onClick={ () => showName( false ) } type="button">
							{ __( 'Cancel', 'search-regex' ) }
						</button>
					</form>
				</Modal>
			) }
			<Select
				name="saved-search"
				value={ currentPreset?.id ?? '0' }
				disabled={ status === STATUS_IN_PROGRESS || searchStatus === STATUS_IN_PROGRESS }
				items={ status === STATUS_IN_PROGRESS ? savingItem : items }
				onChange={ changePreset }
			/>

			<DropdownMenu menu={ presetActions } align="left" disabled={ status === STATUS_IN_PROGRESS } />
		</div>
	);
}

export default Presets;
