import { __ } from '@wordpress/i18n';
import { ReactNode } from 'react';
import PresetFlags from './preset-flags';
import Phrase from './phrase';
import { getActions } from '../search-replace/actions/constants';
import type { PresetValue } from '../../types/preset';

interface PresetEntryProps {
	children: ReactNode;
	preset: PresetValue;
}

function PresetEntry( props: PresetEntryProps ) {
	const { children, preset } = props;
	const { search, name, tags } = preset;
	const { searchPhrase = '', replacement = '', filters = [], action } = search;
	const description = getActions( true, true ).find( ( item ) => item.value === action );

	return (
		<>
			<td className="searchregex-preset__name">{ name }</td>
			<td className="searchregex-preset__search">
				{ searchPhrase.length > 0 && (
					<p>
						<strong>{ __( 'Search', 'search-regex' ) }</strong>:{ ' ' }
						<Phrase phrase={ searchPhrase } tags={ tags } />
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
						<strong>{ __( 'Replace', 'search-regex' ) }</strong>:{ ' ' }
						<Phrase phrase={ replacement } tags={ tags } />
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
