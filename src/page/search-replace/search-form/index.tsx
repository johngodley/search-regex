import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import Presets from '../../../component/presets';
import Form from './form';
import { getHeaderClass } from '../../../lib/preset-utils';
import { useSearchStore } from '../../../stores/search-store';
import { usePresetStore } from '../../../stores/preset-store';

function SearchForm() {
	const search = useSearchStore( ( state ) => state.search );
	const setSearch = useSearchStore( ( state ) => state.setSearch );
	// Subscribe to computed isBusy instead of status/replaceAll separately
	const isBusy = useSearchStore( ( state ) => state.isBusy );
	const mode = useSearchStore( ( state ) => state.mode );
	const currentPreset = usePresetStore( ( state ) => state.currentPreset );

	const headerClass = getHeaderClass( currentPreset ? currentPreset.tags : [] );

	return (
		<table>
			<tbody>
				{ mode === 'advanced' && (
					<tr className={ clsx( headerClass ) }>
						<th>{ __( 'Preset', 'search-regex' ) }</th>
						<td>
							<Presets />
						</td>
					</tr>
				) }

				<Form
					search={ search as any }
					onSetSearch={ setSearch as any }
					isBusy={ isBusy }
					preset={ currentPreset }
				/>
			</tbody>
		</table>
	);
}

export default SearchForm;
