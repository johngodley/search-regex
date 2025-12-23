import { type ChangeEvent } from 'react';

import Logic from '../logic';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import type { SchemaColumn, FilterItem } from '../../../../types/search';

interface FilterStringProps {
	disabled: boolean;
	item: FilterItem;
	onChange: ( values: Partial< FilterItem > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

export default function FilterString( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
}: FilterStringProps ): JSX.Element {
	const { logic = 'equals', value = '', flags = [ 'case' ] } = item;
	const remote = schema.options === 'api' && flags.indexOf( 'regex' ) === -1 ? fetchData : false;

	return (
		<>
			<Logic
				type="string"
				value={ logic }
				disabled={ disabled }
				onChange={ ( newLogic ) => onChange( { logic: newLogic, flags: [ 'case' ] } ) }
			/>

			{ flags.indexOf( 'multi' ) === -1 ? (
				<DropdownText
					value={ value }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { value: newValue } ) }
					{ ...( remote !== false && { fetchData: remote } ) }
				/>
			) : (
				<textarea
					value={ value }
					disabled={ disabled }
					onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) => onChange( { value: ev.target.value } ) }
				/>
			) }

			<SearchFlags
				flags={ flags }
				disabled={ disabled }
				onChange={ ( newFlags ) => onChange( { flags: newFlags } ) }
				allowRegex={ logic === 'equals' || logic === 'notcontains' }
				allowMultiline={ schema.multiline || false }
			/>
		</>
	);
}
