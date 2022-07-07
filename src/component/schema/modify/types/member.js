/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import { DropdownText, Select } from '@wp-plugin-components';
import { setLabel } from '../../../../state/search/action';
import { getLabel } from '../../../../state/search/selector';

function ModifyMember( props ) {
	const { disabled, item, onChange, schema, fetchData, fixOperation, localLabels = [] } = props;
	const { operation = 'include', values = [] } = item;
	const remote = schema.options === 'api' ? fetchData : false;
	const { labels } = useSelector( ( state ) => state.search );
	const dispatch = useDispatch();
	const isJoin = schema.join !== undefined;

	if ( ! remote ) {
		return (
			<Select
				name="member"
				items={ schema.options }
				value={ values.length > 0 ? values[ 0 ] : schema.options[ 0 ].value }
				disabled={ disabled }
				onChange={ ( ev ) => onChange( { values: [ ev.target.value ] } ) }
			/>
		);
	}

	return (
		<>
			{ isJoin && ! fixOperation && (
				<Operation
					type="member"
					value={ operation }
					disabled={ disabled }
					onChange={ ( value ) => onChange( { operation: value } ) }
				/>
			) }

			<DropdownText
				value={ values }
				disabled={ disabled }
				onChange={ ( newValue, newLabel ) => onChange( { values: newValue, label: newLabel } ) }
				fetchData={ remote }
				loadOnFocus={ schema.preload }
				maxChoices={ isJoin ? 20 : 1 }
				onlyChoices
				setLabel={ ( labelId, labelValue ) =>
					dispatch( setLabel( schema.column + '_' + labelId, labelValue ) )
				}
				getLabel={ ( labelId ) => getLabel( labels.concat( localLabels ), schema.column + '_' + labelId ) }
			/>
		</>
	);
}

export default ModifyMember;
