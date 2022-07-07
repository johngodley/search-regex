/**
 * Internal dependencies
 */

import { apiFetch } from '@wp-plugin-lib';
import SearchRegexApi from '../../../lib/api-request';
import ReplaceType from './types';

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('../state/search/type').Schema} props.schema
 * @param {import('../state/search/type').ResultColumn} props.column
 * @param {import('../state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 */
function ReplaceColumn( props ) {
	const { disabled, schema, column, setReplacement, replacement, rowId, source, context } = props;

	function fetchData( value ) {
		return apiFetch( SearchRegexApi.source.complete( source, column.column_id, value ) );
	}

	function loadColumn() {
		return apiFetch( SearchRegexApi.source.loadRow( source, rowId ) ).then( ( data ) => {
			return data.result.find( ( item ) => item.column === column.column_id );
		} );
	}

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

export default ReplaceColumn;
