/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */

import PresetFlags from './preset-flags';
import Phrase from './phrase';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

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
	const { searchPhrase, replacement } = search;

	return (
		<>
			<td className="searchregex-preset__name">{ name }</td>
			<td className="searchregex-preset__search">
				<p>
					<strong>{ __( 'Search' ) }</strong>: <Phrase phrase={ searchPhrase } tags={ tags } />
				</p>
				<p>
					<strong>{ __( 'Replace' ) }</strong>: <Phrase phrase={ replacement } tags={ tags } />
				</p>

				{ children }
			</td>
			<td className="searchregex-preset__flags">
				<PresetFlags preset={ preset } />
			</td>
		</>
	);
}

export default PresetEntry;
