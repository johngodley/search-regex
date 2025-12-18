import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import DatePicker from 'react-datepicker';
import Operation from '../operation';
import { Select } from '@wp-plugin-components';
import type { SchemaColumn, ModifyDateColumn } from '../../../../types/search';

interface ModifyDateProps {
	disabled: boolean;
	item: ModifyDateColumn;
	onChange: ( values: Partial< ModifyDateColumn > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
	fixOperation?: string;
}

export default function ModifyDate( { disabled, item, onChange, fixOperation }: ModifyDateProps ): JSX.Element {
	const { operation = fixOperation || 'set', value = '', unit = 'second' } = item;

	return (
		<>
			{ ! fixOperation && (
				<Operation
					type="date"
					value={ operation }
					disabled={ disabled }
					onChange={ ( newOperation ) =>
						onChange( { operation: newOperation as 'set' | 'increment' | 'decrement', unit: 'second' } )
					}
				/>
			) }

			{ ( operation === 'set' || operation === '' ) && (
				<DatePicker
					selected={ typeof value === 'string' ? new Date( value ) : value }
					onChange={ ( date ) => onChange( { value: date || '' } ) }
					showTimeSelect
					dateFormat="MMMM d, yyyy hh:mm"
					withPortal={ !! fixOperation }
				/>
			) }

			{ operation !== 'set' && operation !== '' && (
				<>
					<input
						type="number"
						value={ typeof value === 'string' ? value : '' }
						onChange={ ( ev: ChangeEvent< HTMLInputElement > ) => onChange( { value: ev.target.value } ) }
						disabled={ disabled }
					/>
					<Select
						name="unit"
						value={ unit }
						onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
							onChange( { unit: ev.target.value as 'second' | 'hour' | 'day' | 'month' | 'year' } )
						}
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
