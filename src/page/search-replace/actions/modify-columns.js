/**
 * External dependencies
 */

import { useSelector } from 'react-redux';
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { Select, Button } from '@wp-plugin-components';
import { getSchema, getNewAction } from '../../../state/search/selector';

function getColumns( schema, existing ) {
	return schema.columns
		.filter( ( column ) => ( column.modify === undefined ? true : column.modify ) )
		.filter( ( column ) =>
			existing.find( ( item ) => item.column === column.column ) === undefined ||
			column.join !== undefined ||
			column.type === 'keyvalue'
				? column
				: false
		)
		.map( ( column ) => ( { label: column.title, value: column.column } ) );
}

function ModifyColumns( props ) {
	const { disabled, columns, source, onSetSearch } = props;
	const { schema } = useSelector( ( state ) => state.search );
	const filteredColumns = getColumns( getSchema( schema, source ), columns );
	const [ modify, setModify ] = useState( filteredColumns.length > 0 ? filteredColumns[ 0 ].value : '' );

	useEffect(() => {
		setModify( filteredColumns.length > 0 ? filteredColumns[ 0 ].value : '' );
	}, [ columns ]);

	function addActionOption() {
		const newAction = getNewAction( modify ? modify : filteredColumns[ 0 ].value, getSchema( schema, source ) );

		onSetSearch( {
			actionOption: columns.concat( [ newAction ] ),
		} );
	}

	if ( filteredColumns.length === 0 ) {
		return null;
	}

	return (
		<>
			<span>{ __( 'Column' ) }</span>
			<Select
				items={ filteredColumns }
				name="modify"
				value={ modify }
				disabled={ disabled }
				className="searchregex-search__action__modify"
				onChange={ ( ev ) => setModify( ev.target.value ) }
			/>
			<Button disabled={ disabled } onClick={ addActionOption }>
				{ __( 'Add' ) }
			</Button>
		</>
	);
}

export default ModifyColumns;
