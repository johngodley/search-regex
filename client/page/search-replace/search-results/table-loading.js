/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import { Placeholder } from 'wp-plugin-components';

function TableLoading( { columns } ) {
	const placeholders = [];

	for ( let index = 0; index < columns; index++ ) {
		placeholders.push( <td key={ index }><Placeholder /></td> );
	}

	return (
		<tr>
			{ placeholders }
		</tr>
	);
}

export default TableLoading;
