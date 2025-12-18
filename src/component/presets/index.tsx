import { useState, type FormEvent, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import type { ThunkDispatch } from 'redux-thunk';
import './style.scss';
import { Select, DropdownMenu, Modal } from '@wp-plugin-components';
import {
	setPreset,
	savePreset as savePresetAction,
	updatePreset as updatePresetAction,
} from '../../state/preset/action';
import { STATUS_IN_PROGRESS } from '../../state/settings/type';
import type { PresetValue } from '../../types/preset';
import type { SearchValues } from '../../types/search';

interface PresetsOwnProps {
	presets: PresetValue[];
	currentPreset: string;
	search: SearchValues;
	status: string;
	searchStatus: string;
}

interface PresetsDispatchProps {
	onChangePreset: ( preset: PresetValue | undefined ) => void;
	onSavePreset: ( name: string, searchValues: SearchValues ) => void;
	onUpdatePreset: ( id: string, searchValues: { search: SearchValues } ) => void;
}

type PresetsProps = PresetsOwnProps & PresetsDispatchProps;

interface RootState {
	preset: {
		presets: PresetValue[];
		currentPreset: string;
		status: string;
	};
	search: {
		search: SearchValues;
		status: string;
	};
}

function Presets( props: PresetsProps ): JSX.Element {
	const { presets, currentPreset, onChangePreset, onSavePreset, search, status, onUpdatePreset, searchStatus } =
		props;
	const [ askName, showName ] = useState( false );
	const [ presetName, setPresetName ] = useState( '' );
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
		onUpdatePreset( currentPreset, { search } );
	};

	const presetActions: JSX.Element[] = [];

	if ( ! currentPreset || currentPreset === '0' ) {
		presetActions.push(
			<button type="button" onClick={ askPresetName } key="save-new">
				{ __( 'Save search as new preset', 'search-regex' ) }
			</button>
		);
	}

	if ( currentPreset && currentPreset !== '0' ) {
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
				value={ currentPreset }
				disabled={ status === STATUS_IN_PROGRESS || searchStatus === STATUS_IN_PROGRESS }
				items={ status === STATUS_IN_PROGRESS ? savingItem : items }
				onChange={ changePreset }
			/>

			<DropdownMenu menu={ presetActions } align="left" disabled={ status === STATUS_IN_PROGRESS } />
		</div>
	);
}

function mapDispatchToProps( dispatch: ThunkDispatch< RootState, unknown, any > ): PresetsDispatchProps {
	return {
		onChangePreset: ( preset: PresetValue | undefined | null ) => {
			dispatch( setPreset( preset ?? null ) );
		},

		onSavePreset: ( name: string, searchValues: SearchValues ) => {
			void dispatch( savePresetAction( name, searchValues ) );
		},

		onUpdatePreset: ( id: string, searchValues: { search: SearchValues } ) => {
			void dispatch( updatePresetAction( id, searchValues ) );
		},
	};
}

function mapStateToProps( state: RootState ): PresetsOwnProps {
	const { presets, currentPreset, status } = state.preset;
	const { search } = state.search;

	return {
		presets,
		currentPreset,
		search,
		status,
		searchStatus: state.search.status,
	};
}

export default connect< PresetsOwnProps, PresetsDispatchProps, Record< string, never >, RootState >(
	mapStateToProps,
	mapDispatchToProps
)( Presets );
