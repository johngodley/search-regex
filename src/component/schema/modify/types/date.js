/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';
import DatePicker from 'react-datepicker';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import { Select } from '@wp-plugin-components';

function ModifyDate( props ) {
	const { disabled, item, onChange, fixOperation } = props;
	const { operation = fixOperation || 'set', value = '', unit = 'second' } = item;

	return (
		<>
			{ ! fixOperation && (
				<Operation
					type="date"
					value={ operation }
					disabled={ disabled }
					onChange={ ( value ) => onChange( { operation: value, unit: 'second' } ) }
				/>
			) }

			{ ( operation === 'set' || operation === '' ) && (
				<DatePicker
					selected={ value }
					onChange={ ( date ) => onChange( { value: date } ) }
					showTimeSelect
					dateFormat="MMMM d, yyyy hh:mm"
					withPortal={ fixOperation }
				/>
			) }

			{ operation !== 'set' && operation !== '' && (
				<>
					<input
						type="number"
						value={ value }
						onChange={ ( ev ) => onChange( { value: ev.target.value } ) }
						disabled={ disabled }
					/>
					<Select
						name="unit"
						value={ unit }
						onChange={ ( ev ) => onChange( { unit: ev.target.value } ) }
						disabled={ disabled }
						items={ [
							{
								value: 'second',
								label: __( 'Seconds', 'search-regex' ),
							},
							{
								value: 'minute',
								label: __( 'Minutes', 'search-regex' ),
							},
							{
								value: 'hour',
								label: __( 'Hours', 'search-regex' ),
							},
							{
								value: 'day',
								label: __( 'Days', 'search-regex' ),
							},
							{
								value: 'week',
								label: __( 'Weeks', 'search-regex' ),
							},
							{
								value: 'month',
								label: __( 'Months', 'search-regex' ),
							},
							{
								value: 'year',
								label: __( 'Year', 'search-regex' ),
							},
						] }
					/>
				</>
			) }
		</>
	);
}

export default ModifyDate;
