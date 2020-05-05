/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import { showInvisible } from './highlight-tools';

const ReplacementPhrase = ( { match, originalMatch } ) => {
	if ( match === null ) {
		return <strike>{ originalMatch }</strike>
	}

	return showInvisible( match );
}

export default ReplacementPhrase;
