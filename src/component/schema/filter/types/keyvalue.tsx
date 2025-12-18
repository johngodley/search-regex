import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import Logic from '../logic';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import type { SchemaColumn, FilterItem } from '../../../../types/search';

interface FilterKeyValueProps {
	disabled: boolean;
	item: FilterItem;
	onChange: ( values: Partial< FilterItem > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

export default function FilterKeyValue( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
}: FilterKeyValueProps ): JSX.Element {
	const {
		key = '',
		keyLogic = 'equals',
		keyFlags = [ 'case' ],
		value = '',
		valueLogic = 'any',
		valueFlags = [ 'case' ],
	} = item;
	const remote =
		schema.options === 'api' ? ( remoteValue: string ) => fetchData( remoteValue ) as Promise< any > : undefined;

	return (
		<div className="searchregex-filter__keyvalue">
			<div className="searchregex-filter__keyvalue__item">
				<span>{ __( 'Meta Key', 'search-regex' ) }</span>

				<Logic
					type="keyvalue"
					value={ keyLogic }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { keyLogic: newValue, key: newValue === 'any' ? '' : key } ) }
				/>

				<DropdownText
					value={ key }
					disabled={ disabled || keyLogic === 'any' }
					onChange={ ( newValue ) =>
						onChange( { key: Array.isArray( newValue ) ? newValue[ 0 ] : newValue } )
					}
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
				<span>{ __( 'Meta Value', 'search-regex' ) }</span>

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
						onChange={ ( newValue ) =>
							onChange( { value: Array.isArray( newValue ) ? newValue[ 0 ] : newValue } )
						}
					/>
				) : (
					<textarea
						value={ value }
						disabled={ disabled || valueLogic === 'any' }
						onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
							onChange( { value: ev.target.value } )
						}
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
