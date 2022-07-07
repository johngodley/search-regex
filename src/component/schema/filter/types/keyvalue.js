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

function FilterKeyValue( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const {
		key = '',
		keyLogic = 'equals',
		keyFlags = [ 'case' ],
		value = '',
		valueLogic = 'any',
		valueFlags = [ 'case' ],
	} = item;
	const remote = schema.options === 'api' ? fetchData : false;

	return (
		<div className="searchregex-filter__keyvalue">
			<div className="searchregex-filter__keyvalue__item">
				<span>{ __( 'Meta Key' ) }</span>

				<Logic
					type="keyvalue"
					value={ keyLogic }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { keyLogic: newValue, key: newValue === 'any' ? '' : key } ) }
				/>

				<DropdownText
					value={ key }
					disabled={ disabled || keyLogic === 'any' }
					onChange={ ( newValue ) => onChange( { key: newValue } ) }
					fetchData={ remote }
					loadOnFocus={ schema.preload }
				/>

				<SearchFlags
					flags={ keyFlags }
					disabled={ disabled || keyLogic === 'any' }
					onChange={ ( newFlags ) => onChange( { keyFlags: newFlags } ) }
					allowRegex={ false }
					allowMultiline={ false }
				/>
			</div>
			<div className="searchregex-filter__keyvalue__item">
				<span>{ __( 'Meta Value' ) }</span>

				<Logic
					type="keyvalue"
					value={ valueLogic }
					disabled={ disabled }
					onChange={ ( newValue ) =>
						onChange( { valueLogic: newValue, value: newValue === 'any' ? '' : value } )
					}
				/>

				{ valueFlags.indexOf( 'multi' ) === -1 ? (
					<DropdownText
						value={ value }
						disabled={ disabled || valueLogic === 'any' }
						onChange={ ( newValue ) => onChange( { value: newValue } ) }
					/>
				) : (
					<textarea
						value={ value }
						disabled={ disabled || valueLogic === 'any' }
						onChange={ ( ev ) => onChange( { value: ev.target.value } ) }
					/>
				) }

				<SearchFlags
					flags={ valueFlags }
					disabled={ disabled || valueLogic === 'any' }
					onChange={ ( newFlags ) => onChange( { valueFlags: newFlags } ) }
					allowRegex={ false }
					allowMultiline={ true }
				/>
			</div>
		</div>
	);
}

export default FilterKeyValue;
