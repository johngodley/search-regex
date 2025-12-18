import { useSelector } from 'react-redux';
import { useEffect, useState, useMemo, ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Select, Button } from '@wp-plugin-components';
import { getSchema, getNewAction } from '../../../state/search/selector';
import type { Schema } from '../../../types/search';

interface Column {
	label: string;
	value: string;
}

interface ModifyActionOption {
	column: string;
	[ key: string ]: unknown;
}

interface RootState {
	search: {
		schema: Schema[];
	};
}

interface ModifyColumnsProps {
	disabled: boolean;
	columns: ModifyActionOption[];
	source: string;
	onSetSearch: ( values: { actionOption: ModifyActionOption[] } ) => void;
}

function getColumns( schema: Schema, existing: ModifyActionOption[] ): Column[] {
	return schema.columns
		.filter( ( column ) => column.column && column.title )
		.filter( ( column ) => ( column.modify === undefined ? true : column.modify ) )
		.filter( ( column ) =>
			existing.find( ( item ) => item.column === column.column ) === undefined ||
			column.join !== undefined ||
			column.type === 'keyvalue'
				? column
				: false
		)
		.map( ( column ) => ( { label: column.title!, value: column.column! } ) );
}

function ModifyColumns( props: ModifyColumnsProps ) {
	const { disabled, columns, source, onSetSearch } = props;
	const { schema } = useSelector( ( state: RootState ) => state.search );
	const sourceSchema = getSchema( schema, source );
	const filteredColumns = useMemo(
		() => ( sourceSchema ? getColumns( sourceSchema, columns ) : [] ),
		[ sourceSchema, columns ]
	);
	const [ modify, setModify ] = useState( filteredColumns.length > 0 ? filteredColumns[ 0 ].value : '' );

	useEffect( () => {
		setModify( filteredColumns.length > 0 ? filteredColumns[ 0 ].value : '' );
	}, [ filteredColumns ] );

	function addActionOption() {
		if ( ! sourceSchema ) {
			return;
		}

		const newAction = getNewAction( modify ? modify : filteredColumns[ 0 ].value, sourceSchema );

		onSetSearch( {
			actionOption: columns.concat( [ newAction as unknown as ModifyActionOption ] ),
		} );
	}

	if ( ! sourceSchema || filteredColumns.length === 0 ) {
		return null;
	}

	return (
		<>
			<span>{ __( 'Column', 'search-regex' ) }</span>
			<Select
				items={ filteredColumns }
				name="modify"
				value={ modify }
				disabled={ disabled }
				className="searchregex-search__action__modify"
				onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => setModify( ev.target.value ) }
			/>
			<Button disabled={ disabled } onClick={ addActionOption }>
				{ __( 'Add', 'search-regex' ) }
			</Button>
		</>
	);
}

export default ModifyColumns;
