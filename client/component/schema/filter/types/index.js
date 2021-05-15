/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */

import FilterInteger from './integer';
import FilterDate from './date';
import FilterString from './string';
import FilterMember from './member';
import FilterKeyValue from './keyvalue';

/** @typedef {import('state/search/type.js').SchemaColumn} SchemaColumn */

/**
 * Modify type
 * @param {object} props Component props
 * @param {SchemaColumn} props.schema - Type of modifier
 */
function FilterType( props ) {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <FilterInteger { ...props } />;
	}

	if ( type === 'date' ) {
		return <FilterDate { ...props } />;
	}

	if ( type === 'string' ) {
		return <FilterString { ...props } />;
	}

	if ( type === 'member' ) {
		return <FilterMember { ...props } />;
	}

	if ( type === 'keyvalue' ) {
		return <FilterKeyValue { ...props } />;
	}

	return null;
}

export default FilterType;
