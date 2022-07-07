/**
 * External dependencies
 */

import DatePicker from 'react-datepicker';
import { parseISO } from 'date-fns';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import Logic from '../logic';
import './style.scss';

function FilterDate( props ) {
	const { disabled, item, onChange } = props;
	const { logic = '=', startValue = 0, endValue = 0 } = item;
	const startValueDate = typeof startValue === 'string' ? parseISO( startValue ) : startValue;
	const endValueDate = typeof endValue === 'string' ? parseISO( endValue ) : endValue;

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
					onChange={ ( date ) => onChange( { startValue: date } ) }
					showTimeSelect
					dateFormat="MMMM d, yyyy hh:mm"
				/>
			) }

			{ logic === 'range' &&
				__( 'between {{first/}} and {{second/}}', {
					components: {
						first: (
							<DatePicker
								selected={ startValueDate }
								onChange={ ( date ) => onChange( { startValue: date } ) }
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
								onChange={ ( date ) => onChange( { endValue: date } ) }
								selectsEnd
								startDate={ startValueDate }
								endDate={ endValueDate }
								minDate={ startValueDate }
								showTimeSelect
								dateFormat="MMMM d, yyyy hh:mm"
							/>
						),
					},
				} ) }
		</>
	);
}

export default FilterDate;
