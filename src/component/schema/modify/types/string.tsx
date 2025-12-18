import { useState, useEffect, useMemo, type ChangeEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useSelector } from 'react-redux';
import Operation from '../operation';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import type { SchemaColumn, ModifyStringColumn, FilterItem, Filter } from '../../../../types/search';

interface ModifyStringProps {
	disabled: boolean;
	item: ModifyStringColumn;
	onChange: ( values: Partial< ModifyStringColumn > ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
}

function canRemote( flags: string[] ): boolean {
	return flags.indexOf( 'regex' ) === -1 && flags.indexOf( 'multi' ) === -1;
}

function getFilterForAction(
	filters: Filter[],
	column: ModifyStringColumn,
	globalSearch: string,
	globalFlags: string[]
): FilterItem[] {
	const columns: FilterItem[] = [];

	if ( globalSearch ) {
		columns.push( { column: 'global', value: globalSearch, logic: 'contains', flags: globalFlags } );
	}

	for ( let index = 0; index < filters.length; index++ ) {
		if ( filters[ index ].type === column.source ) {
			for ( let itemIndex = 0; itemIndex < filters[ index ].items.length; itemIndex++ ) {
				if ( filters[ index ].items[ itemIndex ].column === column.column ) {
					const filter = filters[ index ].items[ itemIndex ];

					if ( filter.logic !== 'notcontains' && filter.logic !== 'notequals' && filter.value ) {
						columns.push( filters[ index ].items[ itemIndex ] );
					}
				}
			}
		}
	}

	return columns;
}

function escapeRegExp( string: string ): string {
	return string.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

function getSearchOperationRegex( filter: FilterItem ): string[] {
	if (
		filter.logic === 'begins' ||
		filter.logic === 'ends' ||
		( filter.flags && filter.flags.indexOf( 'regex' ) !== -1 )
	) {
		return [ ...( filter.flags || [] ), 'regex' ];
	}

	return filter.flags || [];
}

function getSearchOperationValue( filter: FilterItem ): string {
	if ( filter.logic === 'begins' && filter.value ) {
		return '^' + escapeRegExp( filter.value );
	}

	if ( filter.logic === 'ends' && filter.value ) {
		return escapeRegExp( filter.value ) + '$';
	}

	return filter.value || '';
}

function getOperation( operation: string, filters: FilterItem[] ): Partial< ModifyStringColumn > {
	const found = filters.find( ( filter ) => filter.column + '-' + filter.value === operation );

	return {
		operation: found ? 'replace' : ( operation as 'set' | 'replace' | 'regex' ),
		searchValue: found ? getSearchOperationValue( found ) : '',
		searchFlags: found ? getSearchOperationRegex( found ) : [],
	};
}

export default function ModifyString( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
}: ModifyStringProps ): JSX.Element {
	const { operation = 'set', searchValue = '', replaceValue = '', searchFlags = [ 'case' ] } = item;
	const [ localOperation, setLocalOperation ] = useState( 'set' );
	const remote = schema.options === 'api' && canRemote( searchFlags ) ? fetchData : false;
	const {
		filters,
		searchPhrase,
		searchFlags: globalFlags,
	} = useSelector(
		( state: { search: { search: { filters: Filter[]; searchPhrase: string; searchFlags: string[] } } } ) =>
			state.search.search
	);
	const modifiedFilters = useMemo(
		() => getFilterForAction( filters, item, searchPhrase, globalFlags ),
		[ filters, item, searchPhrase, globalFlags ]
	);
	const searchProps = {
		value: searchValue,
		disabled,
		onChange: ( newValue: string ) => onChange( { searchValue: newValue } ),
	};
	const replaceProps = {
		value: replaceValue,
		disabled,
		onChange: ( newValue: string ) => onChange( { replaceValue: newValue } ),
		length: schema.length ? schema.length : 0,
	};

	useEffect( () => {
		onChange( getOperation( localOperation, modifiedFilters ) );
	}, [ localOperation, modifiedFilters, onChange ] );

	const searchPropsWithFetch =
		searchFlags.indexOf( 'multi' ) === -1
			? { ...searchProps, fetchData: remote !== false ? remote : undefined }
			: searchProps;
	const replacePropsWithFetch =
		searchFlags.indexOf( 'multi' ) === -1
			? { ...replaceProps, fetchData: remote !== false ? remote : undefined }
			: replaceProps;

	return (
		<>
			<Operation
				type="string"
				value={ localOperation }
				disabled={ disabled }
				onChange={ setLocalOperation }
				extraItems={ modifiedFilters.map( ( filterItem ) => ( {
					value: filterItem.column + '-' + filterItem.value,
					/* translators: %s: value being replaced */
					label: sprintf( __( 'Replace "%s"', 'search-regex' ), ( filterItem.value || '' ).substr( 0, 20 ) ),
				} ) ) }
			/>

			{ operation === 'replace' && localOperation === 'replace' && (
				<>
					{ __( 'Search', 'search-regex' ) }
					{ searchFlags.indexOf( 'multi' ) === -1 ? (
						<DropdownText { ...searchPropsWithFetch } />
					) : (
						<textarea
							{ ...searchProps }
							onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
								onChange( { searchValue: ev.target.value } )
							}
						/>
					) }
					{ __( 'Replace', 'search-regex' ) }
				</>
			) }

			{ searchFlags.indexOf( 'multi' ) === -1 ? (
				<DropdownText { ...replacePropsWithFetch } />
			) : (
				<textarea
					{ ...replaceProps }
					onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
						onChange( { replaceValue: ev.target.value } )
					}
				/>
			) }

			{ ( schema.multiline || ( operation === 'replace' && localOperation === 'replace' ) ) && (
				<SearchFlags
					flags={ searchFlags }
					disabled={ disabled }
					onChange={ ( newFlags ) => onChange( { searchFlags: newFlags } ) }
					allowRegex={ operation === 'replace' }
					allowMultiline={ schema.multiline || false }
					allowCase={ operation === 'replace' }
				/>
			) }
		</>
	);
}
