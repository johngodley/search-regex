/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

function EmptyResults( { columns } ) {
	return (
		<tr>
			<td colSpan={ columns }>{ __( 'No more matching results found.' ) }</td>
		</tr>
	);
}

export default EmptyResults;
