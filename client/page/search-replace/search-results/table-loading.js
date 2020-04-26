/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import Placeholder from 'component/placeholder';

function TableLoading( { columns } ) {
	const placeholders = [];

	for ( let index = 0; index < columns; index++ ) {
		placeholders.push( <td key={ index } colSpan={ index == 0 ? 2 : 1 }><Placeholder /></td> );
	}

	return (
		<tr>
			{ placeholders }
		</tr>
	);
}

export default TableLoading;
