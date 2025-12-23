import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { isLocked, hasTags, getDefaultPresetValues } from '../../../lib/preset-utils';
import { useSearchStore } from '../../../stores/search-store';
import type { PresetValue, PresetTag } from '../../../types/preset';

interface ReplaceFieldProps {
	locked: string[];
	tags: PresetTag[];
	preset: PresetValue | null;
	disabled: boolean;
	headerClass?: string;
}

function ReplaceField( props: ReplaceFieldProps ) {
	const { locked, tags, preset, disabled, headerClass } = props;

	const search = useSearchStore( ( state ) => state.search );
	const setSearch = useSearchStore( ( state ) => state.setSearch );
	const { replacement = null } = search;

	function setTaggedReplace( replacementValue: string | null ) {
		const defaults = getDefaultPresetValues( preset );

		// If the replace is the default non-tagged replace then reset it to an empty string
		setSearch( {
			replacement: replacementValue === ( defaults?.replacement ?? null ) ? '' : replacementValue ?? '',
		} );
	}

	return (
		<>
			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ clsx( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace', 'search-regex' ) }</th>
					<td>
						<input
							type="text"
							disabled={ disabled }
							value={ replacement ?? '' }
							onChange={ ( ev ) => setSearch( { replacement: ev.target.value } ) }
							placeholder={ __( 'Enter global replacement text', 'search-regex' ) }
						/>
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'replacement' ) && hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ clsx( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace', 'search-regex' ) }</th>
					<td>
						<input
							type="text"
							disabled={ disabled }
							value={ replacement ?? '' }
							onChange={ ( ev ) => setTaggedReplace( ev.target.value ) }
							placeholder={ __( 'Enter global replacement text', 'search-regex' ) }
						/>
					</td>
				</tr>
			) }
		</>
	);
}

export default ReplaceField;
