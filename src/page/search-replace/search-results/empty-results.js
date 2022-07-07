/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

function EmptyResults( { columns } ) {
	return (
		<tr>
			<td colSpan={ columns }>{ __( 'No more matching results found.' ) }</td>
		</tr>
	);
}

export default EmptyResults;
