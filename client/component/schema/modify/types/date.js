/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import DatePicker from 'react-datepicker';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import { Select } from 'wp-plugin-components';

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
								label: __( 'Seconds' ),
							},
							{
								value: 'minute',
								label: __( 'Minutes' ),
							},
							{
								value: 'hour',
								label: __( 'Hours' ),
							},
							{
								value: 'day',
								label: __( 'Days' ),
							},
							{
								value: 'week',
								label: __( 'Weeks' ),
							},
							{
								value: 'month',
								label: __( 'Months' ),
							},
							{
								value: 'year',
								label: __( 'Year' ),
							},
						] }
					/>
				</>
			) }
		</>
	);
}

export default ModifyDate;
