import { __ } from '@wordpress/i18n';

interface EmptyResultsProps {
	columns: number;
}

function EmptyResults( { columns }: EmptyResultsProps ) {
	return (
		<tr>
			<td colSpan={ columns }>{ __( 'No matching results found.', 'search-regex' ) }</td>
		</tr>
	);
}

export default EmptyResults;
