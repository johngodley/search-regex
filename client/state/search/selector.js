/**
 * External dependencies
 */

import { translate as __ } from 'lib/locale';

/**
 * Internal dependencies
 */

import { STATUS_COMPLETE } from 'state/settings/type';

export const getSearchOptions = () => [
	{
		value: 'regex',
		label: __( 'Regular Expression' ),
	},
	{
		value: 'case',
		label: __( 'Ignore Case' ),
	},
];

export const getPerPage = () => ( [
	{
		value: 25,
		label: __( '25 per page ' ),
	},
	{
		value: 50,
		label: __( '50 per page ' ),
	},
	{
		value: 100,
		label: __( '100 per page' ),
	},
	{
		value: 250,
		label: __( '250 per page' ),
	},
	{
		value: 500,
		label: __( '500 per page' ),
	},
] );

export const isAlreadyFinished = ( state ) => state.status === STATUS_COMPLETE || state.status === null;

export function hasReplaceFinished( state, action ) {
	const replaceCount = action.results.rows + state.replaceCount;
	const total = action.totals.matched_rows ? action.totals.matched_rows : ( action.totals.rows ? action.totals.rows : state.totals.rows );

	if ( action.progress.next === false || replaceCount >= total ) {
		return true;
	}

	if ( state.totals.matched_rows > 0 && replaceCount >= state.totals.matched_rows ) {
		return true;
	}

	return false;
}

export const isComplete = ( action, results, direction ) => ( direction === SEARCH_FORWARD && action.progress.next === false ) || ( direction === SEARCH_BACKWARD && action.progress.previous === false ) || results.length >= action.perPage;
export const isAdvancedSearch = ( search ) => search.searchFlags.regex;
