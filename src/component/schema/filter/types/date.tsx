import DatePicker from '../../../../wp-plugin-components/date-picker';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wp-plugin-components';
import Logic from '../logic';
import type { SchemaColumn, FilterItem } from '../../../../types/search';

interface FilterDateProps {
	disabled: boolean;
	item: FilterItem;
	onChange: ( values: Partial< FilterItem > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

function parseToDate( value: string | number | undefined ): Date | null {
	if ( value === undefined || value === null ) {
		return null;
	}
	if ( typeof value === 'string' ) {
		// Use native Date parsing instead of date-fns parseISO
		const date = new Date( value );
		// Check if date is valid
		return isNaN( date.getTime() ) ? null : date;
	}
	return new Date( value );
}

export default function FilterDate( { disabled, item, onChange }: FilterDateProps ): JSX.Element {
	const { logic = '=', startValue, endValue } = item;
	const startValueDate = parseToDate( startValue );
	const endValueDate = parseToDate( endValue );

	return (
		<>
			<Logic
				type="integer"
				value={ logic }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { logic: value } ) }
			/>

			{ logic !== 'range' && (
				<DatePicker
					disabled={ disabled }
					selected={ startValueDate }
					onChange={ ( date ) => {
						if ( date ) {
							onChange( { startValue: date.toISOString() } );
						}
					} }
					showTimeSelect
					dateFormat="MMMM d, yyyy hh:mm"
				/>
			) }

			{ logic === 'range' &&
				createInterpolateElement( __( 'between {{first/}} and {{second/}}', 'search-regex' ), {
					first: (
						<DatePicker
							selected={ startValueDate }
							onChange={ ( date ) => {
								if ( date ) {
									onChange( { startValue: date.toISOString() } );
								}
							} }
							selectsStart
							startDate={ startValueDate }
							endDate={ endValueDate }
							showTimeSelect
							dateFormat="MMMM d, yyyy hh:mm"
						/>
					),
					second: (
						<DatePicker
							selected={ endValueDate }
							onChange={ ( date ) => {
								if ( date ) {
									onChange( { endValue: date.toISOString() } );
								}
							} }
							selectsEnd
							startDate={ startValueDate }
							endDate={ endValueDate }
							{ ...( startValueDate !== null && { minDate: startValueDate } ) }
							showTimeSelect
							dateFormat="MMMM d, yyyy hh:mm"
						/>
					),
				} ) }
		</>
	);
}
