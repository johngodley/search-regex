import { useCallback } from 'react';
import { apiFetch } from '@wp-plugin-lib';
import SearchRegexApi from '../../../lib/api-request';
import ReplaceType from './types';
import type { SchemaColumn, ResultColumn } from '../../../types/search';

interface ReplaceColumnProps {
	disabled: boolean;
	schema: SchemaColumn;
	column: ResultColumn;
	setReplacement: ( values: unknown ) => void;
	replacement: unknown;
	rowId: string;
	source: string;
	context: unknown;
}

export default function ReplaceColumn( {
	disabled,
	schema,
	column,
	setReplacement,
	replacement,
	rowId,
	source,
	context,
}: ReplaceColumnProps ): JSX.Element {
	const fetchData = useCallback(
		( value: string ): Promise< unknown > =>
			apiFetch( SearchRegexApi.source.complete( source, column.column_id, value ) ),
		[ source, column.column_id ]
	);

	const loadColumn = useCallback( (): Promise< unknown > => {
		return apiFetch( SearchRegexApi.source.loadRow( source, rowId ) ).then(
			( data: { result: Array< { column: string } > } ) =>
				data.result.find( ( item ) => item.column === column.column_id )
		);
	}, [ source, rowId, column.column_id ] );

	return (
		<>
			<h5>{ column.column_label }</h5>
			<div className={ 'searchregex-modify searchregex-modify__' + schema.type }>
				<ReplaceType
					disabled={ disabled }
					schema={ schema }
					column={ column }
					fetchData={ fetchData }
					context={ context }
					setReplacement={ setReplacement }
					replacement={ replacement }
					loadColumn={ loadColumn }
				/>
			</div>
		</>
	);
}
