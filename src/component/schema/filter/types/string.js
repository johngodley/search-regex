/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import Logic from '../logic';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';

function FilterString( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const { logic = 'equals', value = '', flags = [ 'case' ] } = item;
	const remote = schema.options === 'api' && flags.indexOf( 'regex' ) === -1 ? fetchData : false;

	return (
		<>
			<Logic
				type="string"
				value={ logic }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { logic: value, flags: [ 'case' ] } ) }
			/>

			{ flags.indexOf( 'multi' ) === -1 ? (
				<DropdownText
					value={ value }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { value: newValue } ) }
					fetchData={ remote }
				/>
			) : (
				<textarea
					value={ value }
					disabled={ disabled }
					onChange={ ( ev ) => onChange( { value: ev.target.value } ) }
				/>
			) }

			<SearchFlags
				flags={ flags }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { flags: value } ) }
				allowRegex={ logic === 'equals' || logic === 'notcontains' }
				allowMultiline={ schema.multiline || false }
			/>
		</>
	);
}

export default FilterString;
