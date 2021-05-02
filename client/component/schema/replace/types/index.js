/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import ReplaceInteger from './integer';
import ReplaceDate from './date';
import ReplaceString from './string';
import ReplaceMember from './member';
import ReplaceKeyValue from './keyvalue';

/** @typedef {import('state/search/type.js').SchemaColumn} SchemaColumn */

/**
 * Modify type
 * @param {object} props Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('state/search/type').SchemaColumn} props.schema
 * @param {import('state/search/type').ResultColumn} props.column
 * @param {import('state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 */
function ReplaceType( props ) {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <ReplaceInteger { ...props } />;
	}

	if ( type === 'date' ) {
		return <ReplaceDate { ...props } />;
	}

	if ( type === 'string' ) {
		return <ReplaceString { ...props } />;
	}

	if ( type === 'member' ) {
		return <ReplaceMember { ...props } />;
	}

	if ( type === 'keyvalue' ) {
		return <ReplaceKeyValue { ...props } />;
	}

	return null;
}

export default ReplaceType;
