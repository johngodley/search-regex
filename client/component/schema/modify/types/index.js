/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import ModifyInteger from './integer';
import ModifyDate from './date';
import ModifyString from './string';
import ModifyMember from './member';
import ModifyKeyValue from './keyvalue';

/** @typedef {import('state/search/type.js').SchemaColumn} SchemaColumn */

/**
 * Modify type
 * @param {object} props Component props
 * @param {SchemaColumn} props.schema - Type of modifier
 */
function ModifyType( props ) {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <ModifyInteger { ...props } />;
	}

	if ( type === 'date' ) {
		return <ModifyDate { ...props } />;
	}

	if ( type === 'string' ) {
		return <ModifyString { ...props } />;
	}

	if ( type === 'member' ) {
		return <ModifyMember { ...props } />;
	}

	if ( type === 'keyvalue' ) {
		return <ModifyKeyValue { ...props } />;
	}

	return null;
}

export default ModifyType;
