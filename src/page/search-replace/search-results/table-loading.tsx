import { ReactNode } from 'react';
import { Placeholder } from '@wp-plugin-components';

interface TableLoadingProps {
	columns: number;
}

function TableLoading( { columns }: TableLoadingProps ) {
	const placeholders: ReactNode[] = [];

	for ( let index = 0; index < columns; index++ ) {
		placeholders.push(
			<td key={ index }>
				<Placeholder />
			</td>
		);
	}

	return <tr>{ placeholders }</tr>;
}

export default TableLoading;
