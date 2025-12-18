import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import classnames from 'classnames';
import { setSearch } from '../../../state/search/action';
import Presets from '../../../component/presets';
import { STATUS_IN_PROGRESS } from '../../../state/settings/type';
import Form from './form';
import { getPreset, getHeaderClass } from '../../../state/preset/selector';
import type { PresetValue } from '../../../types/preset';

interface SearchValues {
	searchPhrase?: string;
	searchFlags?: string[];
	source?: string[];
	perPage?: number;
	replacement?: string | null;
	filters?: unknown[];
	actionOption?: unknown;
	view?: string[];
	[ key: string ]: unknown;
}

interface RootState {
	search: {
		search: SearchValues;
		replaceAll: boolean;
		status: string;
	};
	preset: {
		presets: PresetValue[];
		currentPreset: string | null;
	};
}

interface SearchFormProps {
	search: SearchValues;
	onSetSearch: ( searchValue: SearchValues ) => void;
	replaceAll: boolean;
	currentPreset: PresetValue | null;
	status: string;
}

function SearchForm( { search, onSetSearch, replaceAll, currentPreset, status }: SearchFormProps ) {
	const headerClass = getHeaderClass( currentPreset ? currentPreset.tags : [] );

	return (
		<table>
			<tbody>
				<tr className={ classnames( headerClass ) }>
					<th>{ __( 'Preset', 'search-regex' ) }</th>
					<td>
						<Presets />
					</td>
				</tr>

				<Form
					search={ search as any }
					onSetSearch={ onSetSearch as any }
					isBusy={ status === STATUS_IN_PROGRESS || replaceAll }
					preset={ currentPreset }
				/>
			</tbody>
		</table>
	);
}

/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
function mapDispatchToProps( dispatch: any ) {
	return {
		onSetSearch: ( searchValue: SearchValues ) => {
			dispatch( setSearch( searchValue as any ) );
		},
	};
}
/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */

function mapStateToProps( state: RootState ) {
	const { search, replaceAll, status } = state.search;
	const { presets, currentPreset } = state.preset;

	return {
		search,
		replaceAll,
		status,
		currentPreset: currentPreset ? getPreset( presets, currentPreset ) : undefined,
	};
}

export default connect( mapStateToProps, mapDispatchToProps )( SearchForm as any );
