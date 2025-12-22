import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import Presets from '../../../component/presets';
import { STATUS_IN_PROGRESS } from '../../../lib/constants';
import Form from './form';
import { getHeaderClass } from '../../../lib/preset-utils';
import { useSearchStore } from '../../../stores/search-store';
import { usePresetStore } from '../../../stores/preset-store';

function SearchForm() {
	const search = useSearchStore( ( state ) => state.search );
	const setSearch = useSearchStore( ( state ) => state.setSearch );
	const replaceAll = useSearchStore( ( state ) => state.replaceAll );
	const status = useSearchStore( ( state ) => state.status );
	const currentPreset = usePresetStore( ( state ) => state.currentPreset );

	const headerClass = getHeaderClass( currentPreset ? currentPreset.tags : [] );

	return (
		<table>
			<tbody>
				<tr className={ clsx( headerClass ) }>
					<th>{ __( 'Preset', 'search-regex' ) }</th>
					<td>
						<Presets />
					</td>
				</tr>

				<Form
					search={ search as any }
					onSetSearch={ setSearch as any }
					isBusy={ status === STATUS_IN_PROGRESS || replaceAll }
					preset={ currentPreset }
				/>
			</tbody>
		</table>
	);
}

export default SearchForm;
