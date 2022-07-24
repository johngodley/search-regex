/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { createInterpolateElement } from '@wp-plugin-components';
import Logic from '../logic';
import IntegerInput from '../../../integer-input';

function FilterInteger( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const { logic = 'equals', startValue = '', endValue = '' } = item;
	const remote = schema.options === 'api' ? fetchData : false;
	const canRemote = logic === 'equals' || logic === 'notequals' ? 1 : -1;

	if ( logic === 'has' || logic === 'hasnot' ) {
		return (
			<Logic
				type={ 'integer-join' }
				value={ logic }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { logic: value, startValue: '', endValue: '' } ) }
			/>
		);
	}

	return (
		<>
			<Logic
				type={ schema.joined_by ? 'integer-join' : 'integer' }
				value={ logic }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { logic: value, startValue: '', endValue: '' } ) }
			/>

			{ logic !== 'range' && (
				<IntegerInput
					name="startValue"
					column={ item.column }
					value={ startValue }
					onChange={ onChange }
					disabled={ disabled }
					remote={ canRemote ? remote : false }
				/>
			) }

			{ logic === 'range' &&
				createInterpolateElement(
					__( 'between {{first/}} and {{second/}}', 'search-regex' ),
					{
						first: (
							<IntegerInput
								name="startValue"
								value={ startValue }
								onChange={ onChange }
								disabled={ disabled }
								remote={ false }
								column={ item.column }
							/>
						),
						second: (
							<IntegerInput
								name="endValue"
								value={ endValue }
								onChange={ onChange }
								disabled={ disabled }
								remote={ false }
								column={ item.column }
							/>
						),
					},
				) }
		</>
	);
}

export default FilterInteger;
