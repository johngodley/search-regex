/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { setSearch } from '../../../state/search/action';
import Presets from '../../../component/presets';
import { STATUS_IN_PROGRESS } from '../../../state/settings/type';
import Form from './form';
import { getPreset, getHeaderClass } from '../../../state/preset/selector';

/** @typedef {import('../../../state/search/type').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('../../../state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('../../../state/preset/type.js').PresetValue} PresetValue */

/**
 * @callback SetSearchCallback
 * @param {SearchValues} search
 */

/**
 * The search form
 *
 * @param {object} props - Component props
 * @param {SearchValues} props.search - Search values
 * @param {boolean} props.replaceAll - Are we replacing all matches?
 * @param {string} props.status - Status
 * @param {PresetValue|null} props.currentPreset - Current preset
 * @param {SetSearchCallback} props.onSetSearch
 */
function SearchForm( { search, onSetSearch, replaceAll, currentPreset, status } ) {
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
					search={ search }
					onSetSearch={ onSetSearch }
					isBusy={ status === STATUS_IN_PROGRESS || replaceAll }
					preset={ currentPreset }
				/>
			</tbody>
		</table>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		/**
		 * @param {SearchValues} searchValue
		 */
		onSetSearch: ( searchValue ) => {
			dispatch( setSearch( searchValue ) );
		},
	};
}

function mapStateToProps( state ) {
	const { search, replaceAll, status } = state.search;
	const { presets, currentPreset } = state.preset;

	return {
		search,
		replaceAll,
		status,
		currentPreset: getPreset( presets, currentPreset ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchForm );
