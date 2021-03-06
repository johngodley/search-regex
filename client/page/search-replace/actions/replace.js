/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */
import { isLocked, hasTags, getDefaultPresetValues } from 'state/preset/selector';
import Replace from 'component/replace';
import { setSearch } from 'state/search/action';

function ReplaceField( props ) {
	const { locked, tags, preset, disabled, headerClass } = props;
	const dispatch = useDispatch();
	const {
		search: { replacement },
	} = useSelector( ( state ) => state.search );

	function setTaggedReplace( replacement ) {
		const defaults = getDefaultPresetValues( preset );

		// If the replace is the default non-tagged replace then reset it to an empty string
		dispatch( setSearch( { replacement: replacement === defaults.replacement ? '' : replacement } ) );
	}

	return (
		<>
			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace' ) }</th>
					<td>
						<Replace
							disabled={ disabled }
							setReplace={ ( replacement ) => dispatch( setSearch( { replacement } ) ) }
							replace={ replacement }
							placeholder={ __( 'Enter global replacement text' ) }
						/>
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'replacement' ) && hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<Replace
					preset={ preset }
					disabled={ disabled }
					setReplace={ setTaggedReplace }
					replace={ replacement }
					placeholder={ __( 'Enter global replacement text' ) }
					className={ headerClass }
				/>
			) }
		</>
	);
}

export default ReplaceField;
