import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import Logic from '../logic';
import { DropdownText, MultiOptionDropdown } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import { setLabel } from '../../../../state/search/action';
import { getLabel } from '../../../../state/search/selector';
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
	const { labels } = useSelector( ( state: { search: { labels: unknown } } ) => state.search );
	const dispatch = useDispatch();

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
					value={ values.length === 0 ? '' : values[ 0 ] }
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
					loadOnFocus={ schema.preload }
					maxChoices={ 20 }
					onlyChoices
					setLabel={ ( labelId: string, labelValue: string | null ) => {
						dispatch( setLabel( schema.column + '_' + labelId, labelValue || '' ) );
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
						onChange( {
							values: setValues(
								valueArray,
								'',
								options.map( ( option ) => option.value as string )
							),
						} );
					} }
					multiple={ schema.multiple ?? true }
					disabled={ disabled }
					hideTitle
					title={ schema.title }
					badges
				/>
			) }
		</>
	);
}
