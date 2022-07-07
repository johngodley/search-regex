/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';

function ModifyKeyValue( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const {
		operation = 'add',
		key = '',
		value = '',
		valueFlags = [ 'case' ],
	} = item;
	const remote = schema.options === 'api' ? fetchData : false;

	return (
		<>
			<Operation
				type="keyvalue"
				value={ operation }
				disabled={ disabled }
				onChange={ ( value ) => onChange( { operation: value, key: '', value: '' } ) }
			/>

			<div className="searchregex-filter__keyvalue">
				<div className="searchregex-filter__keyvalue__item">
					<span>{ __( 'Meta Key' ) }</span>

					{ operation === 'replace_key' && __( 'Search' ) }

					<DropdownText
						value={ key }
						disabled={ disabled }
						onChange={ ( newValue ) => onChange( { key: newValue } ) }
						fetchData={ remote && operation !== 'replace_key' ? remote : null }
						loadOnFocus={ schema.preload }
					/>
				</div>

				{ operation !== 'remove' && (
					<div className="searchregex-filter__keyvalue__item">
						<span>{ __( 'Meta Value' ) }</span>

						{ operation === 'replace_value' && __( 'Search' ) }

						{ valueFlags.indexOf( 'multi' ) === -1 ? (
							<DropdownText
								value={ value }
								disabled={ disabled }
								onChange={ ( newValue ) => onChange( { value: newValue } ) }
							/>
						) : (
							<textarea
								value={ value }
								disabled={ disabled }
								onChange={ ( ev ) => onChange( { value: ev.target.value } ) }
							/>
						) }

						<SearchFlags
							flags={ valueFlags }
							disabled={ disabled }
							onChange={ ( newFlags ) => onChange( { valueFlags: newFlags } ) }
							allowRegex={ false }
							allowMultiline
							allowCase={ false }
						/>
					</div>
				) }
			</div>
		</>
	);
}

export default ModifyKeyValue;
