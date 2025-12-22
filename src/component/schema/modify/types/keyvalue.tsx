import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import Operation from '../operation';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import type { SchemaColumn } from '../../../../types/search';

interface ModifyKeyValueColumn {
	column: string;
	source?: string;
	operation?: 'add' | 'remove' | 'replace_key' | 'replace_value';
	key?: string;
	value?: string;
	valueFlags?: string[];
}

interface ModifyKeyValueProps {
	disabled: boolean;
	item: ModifyKeyValueColumn;
	onChange: ( values: Partial< ModifyKeyValueColumn > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

export default function ModifyKeyValue( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
}: ModifyKeyValueProps ): JSX.Element {
	const { operation = 'add', key = '', value = '', valueFlags = [ 'case' ] } = item;
	const remote = schema.options === 'api' ? fetchData : false;

	return (
		<>
			<Operation
				type="keyvalue"
				value={ operation }
				disabled={ disabled }
				onChange={ ( newOperation ) =>
					onChange( {
						operation: newOperation as 'add' | 'remove' | 'replace_key' | 'replace_value',
						key: '',
						value: '',
					} )
				}
			/>

			<div className="searchregex-filter__keyvalue">
				<div className="searchregex-filter__keyvalue__item">
					<span>{ __( 'Meta Key', 'search-regex' ) }</span>

					{ operation === 'replace_key' && __( 'Search', 'search-regex' ) }

					<DropdownText
						value={ key }
						disabled={ disabled }
						onChange={ ( newValue ) => onChange( { key: newValue } ) }
						{ ...( remote && operation !== 'replace_key' && { fetchData: remote } ) }
						{ ...( schema.preload !== undefined && { loadOnFocus: schema.preload } ) }
					/>
				</div>

				{ operation !== 'remove' && (
					<div className="searchregex-filter__keyvalue__item">
						<span>{ __( 'Meta Value', 'search-regex' ) }</span>

						{ operation === 'replace_value' && __( 'Search', 'search-regex' ) }

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
								onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
									onChange( { value: ev.target.value } )
								}
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
