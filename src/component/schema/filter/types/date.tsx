import DatePicker from 'react-datepicker';
import { parseISO } from 'date-fns';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wp-plugin-components';
import Logic from '../logic';
import type { SchemaColumn, FilterItem } from '../../../../types/search';
import './style.scss';

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
		return parseISO( value );
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
					onChange={ ( date ) => onChange( { startValue: date?.toISOString() } ) }
					showTimeSelect
					dateFormat="MMMM d, yyyy hh:mm"
				/>
			) }

			{ logic === 'range' &&
				createInterpolateElement( __( 'between {{first/}} and {{second/}}', 'search-regex' ), {
					first: (
						<DatePicker
							selected={ startValueDate }
							onChange={ ( date ) => onChange( { startValue: date?.toISOString() } ) }
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
							onChange={ ( date ) => onChange( { endValue: date?.toISOString() } ) }
							selectsEnd
							startDate={ startValueDate }
							endDate={ endValueDate }
							minDate={ startValueDate ?? undefined }
							showTimeSelect
							dateFormat="MMMM d, yyyy hh:mm"
						/>
					),
				} ) }
		</>
	);
}
