/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import { setLabel } from 'state/search/action';
import { getLabel } from 'state/search/selector';
import { DropdownText } from 'wp-plugin-components';
import './style.scss';

function IntegerInput( { value, name, onChange, disabled, remote, column } ) {
	const { labels } = useSelector( ( state ) => state.search );
	const dispatch = useDispatch();

	function sanitize( newValue ) {
		const numberOnly = String( newValue )
			.replace( /[^0-9]/g, '' )
			.trim();

		if ( numberOnly.length > 0 ) {
			return String( parseInt( numberOnly, 10 ) );
		}

		return '';
	}

	return (
		<DropdownText
			name={ name }
			className="searchregex-integer-input"
			value={ value }
			disabled={ disabled }
			onBlur={ sanitize }
			onChange={ ( value ) => onChange( { [ name ]: value } ) }
			fetchData={ remote }
			canMakeRequest={ ( value ) => value.length > 0 && value.replace( /[0-9]/g, '' ).length > 0 }
			maxChoices={ remote ? 1 : -1 }
			onlyChoices={ remote ? true : false }
			setLabel={ ( labelId, labelValue ) => dispatch( setLabel( column + '_' + labelId, labelValue ) ) }
			getLabel={ ( labelId, labelValue ) => getLabel( labels, column + '_' + labelId, labelValue ) }
		/>
	);
}

export default IntegerInput;
