/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import IntegerInput from 'component/integer-input';
import { setLabel } from 'state/search/action';
import { getLabel } from 'state/search/selector';
import { DropdownText } from 'wp-plugin-components';

function ModifyInteger( props ) {
	const { disabled, item, onChange, schema, fetchData, fixOperation } = props;
	const { operation = fixOperation || 'set', value = '' } = item;
	const remote = schema.options === 'api' ? fetchData : false;
	const { labels } = useSelector( ( state ) => state.search );
	const dispatch = useDispatch();

	if ( remote ) {
		return (
			<DropdownText
				value={ value === '' || value === undefined ? [] : [ value ] }
				disabled={ disabled }
				onChange={ ( newValue, newLabel ) => onChange( newValue ? { value: newValue[0] } : { value: '' }, newValue ? newLabel[0] : '' ) }
				fetchData={ remote }
				loadOnFocus
				maxChoices={ 1 }
				onlyChoices
				setLabel={ ( labelId, labelValue ) =>
					dispatch( setLabel( schema.column + '_' + labelId, labelValue ) )
				}
				getLabel={ ( labelId ) => getLabel( labels, schema.column + '_' + labelId ) }
			/>
		);
	}

	return (
		<>
			{ ! fixOperation && <Operation
				type="integer"
				value={ operation }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { operation: value, value: '' } ) }
			/> }

			<IntegerInput name="value" value={ value } onChange={ onChange } disabled={ disabled } remote={ remote } />
		</>
	);
}

export default ModifyInteger;
