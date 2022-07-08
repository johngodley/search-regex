/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import PresetFlags from './preset-flags';
import Phrase from './phrase';
import { getActions } from '../search-replace/actions/constants';

/** @typedef {import('../../state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('../../state/preset/type.js').PresetValue} PresetValue */

/**
 * Preset row entry
 *
 * @param {object} props - Component props
 * @param {object} props.children - Child elements
 * @param {PresetValue} props.preset - Preset
 */
function PresetEntry( props ) {
	const { children, preset } = props;
	const { search, name, tags } = preset;
	const { searchPhrase, replacement, filters, action } = search;
	const description = getActions( true, true ).find( ( item ) => item.value === action );

	return (
		<>
			<td className="searchregex-preset__name">{ name }</td>
			<td className="searchregex-preset__search">
				{ searchPhrase.length > 0 && (
					<p>
						<strong>{ __( 'Search', 'search-regex' ) }</strong>: <Phrase phrase={ searchPhrase } tags={ tags } />
					</p>
				) }
				{ filters.length > 0 && (
					<p>
						<strong>{ __( 'Filters', 'search-regex' ) }</strong>: { filters.length }
					</p>
				) }
				{ description && (
					<p>
						<strong>{ __( 'Action', 'search-regex' ) }</strong>: { description.label }
					</p>
				) }
				{ replacement.length > 0 && (
					<p>
						<strong>{ __( 'Replace', 'search-regex' ) }</strong>: <Phrase phrase={ replacement } tags={ tags } />
					</p>
				) }

				{ children }
			</td>
			<td className="searchregex-preset__flags">
				<PresetFlags preset={ preset } />
			</td>
		</>
	);
}

export default PresetEntry;
