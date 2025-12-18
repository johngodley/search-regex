import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useSelector, useDispatch } from 'react-redux';
import { isLocked, hasTags, getDefaultPresetValues } from '../../../state/preset/selector';
import { setSearch } from '../../../state/search/action';
import type { PresetValue, PresetTag } from '../../../types/preset';

interface RootState {
	search: {
		search: {
			replacement: string | null;
		};
	};
}

interface ReplaceFieldProps {
	locked: string[];
	tags: PresetTag[];
	preset: PresetValue | null;
	disabled: boolean;
	headerClass?: string;
}

function ReplaceField( props: ReplaceFieldProps ) {
	const { locked, tags, preset, disabled, headerClass } = props;
	const dispatch = useDispatch();
	const {
		search: { replacement },
	} = useSelector( ( state: RootState ) => state.search );

	function setTaggedReplace( replacementValue: string | null ) {
		const defaults = getDefaultPresetValues( preset );

		/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
		// If the replace is the default non-tagged replace then reset it to an empty string
		dispatch(
			setSearch( {
				replacement: replacementValue === ( defaults?.replacement ?? null ) ? '' : replacementValue ?? '',
			} ) as any
		);
		/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
	}

	return (
		<>
			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace', 'search-regex' ) }</th>
					<td>
						<input
							type="text"
							disabled={ disabled }
							value={ replacement ?? '' }
							onChange={ ( ev ) => {
								/* eslint-disable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
								dispatch( setSearch( { replacement: ev.target.value } ) as any );
								/* eslint-enable @typescript-eslint/no-explicit-any, @typescript-eslint/no-unsafe-call */
							} }
							placeholder={ __( 'Enter global replacement text', 'search-regex' ) }
						/>
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'replacement' ) && hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__replace', headerClass ) }>
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
