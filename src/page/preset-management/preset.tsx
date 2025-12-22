import { useState, MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { useDeletePreset, useUpdatePreset } from '../../hooks/use-presets';
import PresetEdit from './preset-edit';
import PresetEntry from './preset-entry';
import type { PresetValue } from '../../types/preset';

interface PresetProps {
	preset: PresetValue;
}

function cleanPreset( preset: PresetValue ): Partial< PresetValue > {
	const { id, tags, locked, ...rest } = preset;
	const newPreset: Partial< PresetValue > = { ...rest };

	if ( tags.length > 0 ) {
		newPreset.tags = tags;
	}

	if ( locked.length > 0 ) {
		newPreset.locked = locked;
	}

	return newPreset;
}

function Preset( props: PresetProps ) {
	const { preset } = props;
	const { id } = preset;
	const [ isEditing, setEditing ] = useState( false );
	const deleteMutation = useDeletePreset();
	const updateMutation = useUpdatePreset();

	const isSaving = deleteMutation.isPending || updateMutation.isPending;

	function deleteIt( ev: MouseEvent< HTMLAnchorElement > ) {
		ev.preventDefault();

		/* eslint-disable no-alert */
		if ( confirm( __( 'Are you sure you want to delete this preset?', 'search-regex' ) ) ) {
			/* eslint-enable no-alert */
			deleteMutation.mutate( id );
		}
	}

	function editIt( ev: MouseEvent< HTMLAnchorElement > ) {
		ev.preventDefault();
		setEditing( true );
	}

	function updateIt( updatedPreset: PresetValue ) {
		updateMutation.mutate( updatedPreset );
		setEditing( false );
	}

	return (
		<tr className={ isSaving ? 'searchregex-preset__saving' : '' }>
			{ isEditing ? (
				<PresetEdit preset={ preset } onCancel={ () => setEditing( false ) } onUpdate={ updateIt } />
			) : (
				<PresetEntry preset={ preset }>
					<div className="row-actions">
						{ isSaving ? (
							<>&nbsp;</>
						) : (
							<>
								{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
								<a href="#" onClick={ editIt }>
									{ __( 'Edit', 'search-regex' ) }
								</a>{ ' ' }
								|{ ' ' }
								<a href="#" onClick={ deleteIt }>
									{ __( 'Delete', 'search-regex' ) }
								</a>{ ' ' }
								|{ ' ' }
								<CopyToClipboard text={ JSON.stringify( cleanPreset( preset ) ) }>
									<a href="#" onClick={ ( ev ) => ev.preventDefault() }>
										{ __( 'Copy to clipboard', 'search-regex' ) }
									</a>
								</CopyToClipboard>
								{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
							</>
						) }
					</div>
				</PresetEntry>
			) }
		</tr>
	);
}

export default Preset;
