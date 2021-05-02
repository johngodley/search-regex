/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import ContextType from '../context-type';

function ContextValue( { context, rowId, column, schema, setReplacement, className } ) {
	if ( context.type === 'keyvalue' ) {
		return (
			<>
				<ContextValue
					rowId={ rowId }
					column={ column }
					schema={ schema }
					setReplacement={ setReplacement }
					context={ context.key }
					className="searchregex-list__key"
				/>
				=
				<ContextValue
					rowId={ rowId }
					column={ column }
					schema={ schema }
					setReplacement={ setReplacement }
					context={ context.value }
					className="searchregex-list__value"
				/>
			</>
		);
	}

	return (
		<>
			<ContextType
				context={ context }
				rowId={ rowId }
				column={ column }
				schema={ schema }
				setReplacement={ setReplacement }
				className={ className }
			/>
		</>
	);
}

export default ContextValue;
