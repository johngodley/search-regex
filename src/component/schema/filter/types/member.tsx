import { __ } from '@wordpress/i18n';
import Logic from '../logic';
import { DropdownText, MultiOptionDropdown } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import { getLabel } from '../../../../lib/search-utils';
import { useSearchStore } from '../../../../stores/search-store';
import type { SchemaColumn, FilterItem, SelectOption } from '../../../../types/search';

interface FilterMemberProps {
	disabled: boolean;
	item: FilterItem;
	onChange: ( values: Partial< FilterItem > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

function setValues( values: string[], changed: string, all: string[] ): string[] {
	if ( changed === '' ) {
		return values.indexOf( '' ) === -1 ? [] : all;
	}

	return values.filter( ( item ) => item !== '' );
}

function getChangedValue( previous: string[], next: string[] ): string {
	const added = next.find( ( value ) => previous.indexOf( value ) === -1 );
	if ( added !== undefined ) {
		return added;
	}

	const removed = previous.find( ( value ) => next.indexOf( value ) === -1 );
	return removed || '';
}

function getValues( values: string[], allLength: number ): string[] {
	if ( values.length === allLength ) {
		return [ '', ...values ];
	}

	return values;
}

function getOptions( options: SelectOption[] ): SelectOption[] {
	return [
		{
			value: '',
			label: __( 'All', 'search-regex' ),
		},
		...options,
	];
}

export default function FilterMember( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
}: FilterMemberProps ): JSX.Element {
	const { logic = 'include', values = [], flags = [ 'case' ] } = item;
	const remote = schema.options === 'api' ? fetchData : false;

	const labels = useSearchStore( ( state ) => state.labels );
	const setLabels = useSearchStore( ( state ) => state.setLabels );

	const logicComponent = (
		<Logic
			type="member"
			value={ logic }
			disabled={ disabled }
			onChange={ ( value ) => onChange( { logic: value, values: [] } ) }
		/>
	);

	if ( logic === 'contains' || logic === 'notcontains' ) {
		return (
			<>
				{ logicComponent }
				<DropdownText
					value={ values[ 0 ] ?? '' }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { values: [ newValue ] } ) }
				/>
				<SearchFlags
					flags={ flags }
					disabled={ disabled }
					onChange={ ( value ) => onChange( { flags: value } ) }
					allowRegex={ false }
					allowMultiline={ false }
				/>
			</>
		);
	}

	if ( remote ) {
		return (
			<>
				{ logicComponent }
				<DropdownText
					value={ values }
					disabled={ disabled }
					onChange={ ( newValue ) => onChange( { values: newValue } ) }
					fetchData={ remote }
					{ ...( schema.preload !== undefined && { loadOnFocus: schema.preload } ) }
					maxChoices={ 20 }
					onlyChoices
					setLabel={ ( labelId: string, labelValue: string | null ) => {
						const existingIndex = ( labels as Array< { value: string; label: string } > ).findIndex(
							( l ) => l.value === schema.column + '_' + labelId
						);

						if ( existingIndex >= 0 ) {
							const updatedLabels = [ ...( labels as Array< { value: string; label: string } > ) ];
							updatedLabels[ existingIndex ] = {
								value: schema.column + '_' + labelId,
								label: labelValue || '',
							};
							setLabels( updatedLabels );
						} else {
							setLabels( [
								...( labels as Array< { value: string; label: string } > ),
								{ value: schema.column + '_' + labelId, label: labelValue || '' },
							] );
						}
					} }
					getLabel={ ( labelId: string ) =>
						getLabel( labels as { value: string; label: string }[], schema.column + '_' + labelId )
					}
				/>
			</>
		);
	}

	const options = Array.isArray( schema.options ) ? schema.options : [];

	return (
		<>
			{ logicComponent }
			{ ! remote && (
				<MultiOptionDropdown
					options={ getOptions( options ) as any }
					selected={ getValues( values, options.length ) }
					onChange={ ( newValue: string[] | Record< string, string | boolean | undefined > ) => {
						const valueArray = Array.isArray( newValue ) ? newValue : [];
						const previousSelectedValues = getValues( values, options.length );
						const changedValue = getChangedValue( previousSelectedValues, valueArray );
						const optionValues = options.map( ( option ) => option.value as string );

						onChange( {
							values: setValues( valueArray, changedValue, optionValues ),
						} );
					} }
					multiple={ schema.multiple ?? true }
					disabled={ disabled }
					hideTitle
					{ ...( schema.title !== undefined && { title: schema.title } ) }
					badges
				/>
			) }
		</>
	);
}
