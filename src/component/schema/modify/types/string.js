/**
 * External dependencies
 */

import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelector } from 'react-redux';

/**
 * Internal dependencies
 */

import Operation from '../operation';
import { DropdownText } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';

function canRemote( flags ) {
	return flags.indexOf( 'regex' ) === -1 && flags.indexOf( 'multi' ) === -1;
}

function getFilterForAction( filters, column, globalSearch, globalFlags ) {
	const columns = [];

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

function escapeRegExp( string ) {
	return string.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

function getSearchOperationRegex( filter ) {
	if ( filter.logic === 'begins' || filter.logic === 'ends' || filter.flags.indexOf( 'regex' ) !== -1 ) {
		return filter.flags.concat( [ 'regex' ] );
	}

	return filter.flags;
}

function getSearchOperationValue( filter ) {
	if ( filter.logic === 'begins' ) {
		return '^' + escapeRegExp( filter.value );
	}

	if ( filter.logic === 'ends' ) {
		return escapeRegExp( filter.value ) + '$';
	}

	return filter.value;
}

function getOperation( operation, filters ) {
	// Find operation in filters
	const found = filters.find( ( filter ) => filter.column + '-' + filter.value === operation );

	return {
		operation: found ? 'replace' : operation,
		searchValue: found ? getSearchOperationValue( found ) : '',
		searchFlags: found ? getSearchOperationRegex( found ) : [],
	};
}

function ModifyString( props ) {
	const { disabled, item, onChange, schema, fetchData } = props;
	const { operation = 'set', searchValue = '', replaceValue = '', searchFlags = [ 'case' ] } = item;
	const [ localOperation, setLocalOperation ] = useState( 'set' );
	const remote = schema.options === 'api' && canRemote( searchFlags ) ? fetchData : false;
	const { filters, searchPhrase, searchFlags: globalFlags } = useSelector( ( state ) => state.search.search );
	const modifiedFilters = getFilterForAction( filters, item, searchPhrase, globalFlags );
	const searchProps = {
		value: searchValue,
		disabled: disabled,
		onChange: ( newValue ) => onChange( { searchValue: newValue } ),
	};
	const replaceProps = {
		value: replaceValue,
		disabled: disabled,
		onChange: ( newValue ) => onChange( { replaceValue: newValue } ),
		length: schema.length ? schema.length : 0,
	};

	useEffect( () => {
		onChange( getOperation( localOperation, modifiedFilters ) );
	}, [ localOperation, filters ] );

	if ( searchFlags.indexOf( 'multi' ) === -1 ) {
		searchProps.fetchData = remote;
		replaceProps.fetchData = remote;
	}

	return (
		<>
			<Operation
				type="string"
				value={ localOperation }
				disabled={ disabled }
				onChange={ setLocalOperation }
				extraItems={ modifiedFilters.map( ( item ) => ( {
					value: item.column + '-' + item.value,
					label: __( 'Replace "%1s"', {
						args: item.value.substr( 0, 20 ),
					} ),
				} ) ) }
			/>

			{ operation === 'replace' && localOperation === 'replace' && (
				<>
					{ __( 'Search' ) }
					{ searchFlags.indexOf( 'multi' ) === -1 ? (
						<DropdownText { ...searchProps } />
					) : (
						<textarea { ...searchProps } />
					) }
					{ __( 'Replace' ) }
				</>
			) }

			{ searchFlags.indexOf( 'multi' ) === -1 ? (
				<DropdownText { ...replaceProps } />
			) : (
				<textarea { ...replaceProps } />
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

export default ModifyString;
