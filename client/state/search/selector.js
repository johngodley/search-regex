/**
 * External dependencies
 */

import { translate as __ } from 'lib/locale';

/**
 * Internal dependencies
 */

import { STATUS_COMPLETE } from 'state/settings/type';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import { removePostTypes } from 'lib/sources';

/**
 * Return all the search flags
 */
export const getAvailableSearchFlags = () => [
	{
		value: 'regex',
		label: __( 'Regular Expression' ),
	},
	{
		value: 'case',
		label: __( 'Ignore Case' ),
	},
];

/**
 * Return all the per-page values
 */
export const getAvailablePerPage = () => ( [
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

/**
 * Return true if the state is complete or has no status, false otherwise
 * @param {Object} state Current state
 * @returns {Boolean}
 */
export const isAlreadyFinished = ( state ) => state.status === STATUS_COMPLETE || state.status === null;

/**
 * Return the total replacements based on the state and action
 * The total will be the number of matched rows, if it exists. Otherwise it will be the number of returned rows.
 * Failing that it will be the total in the state.
 *
 * @param {Object} state Current state
 * @param {Object} action Current action
 */
export function getReplaceTotal( state, action ) {
	if ( action.totals.matched_rows ) {
		return action.totals.matched_rows;
	}

	if ( action.totals.rows ) {
		return action.totals.rows;
	}

	return state.totals.rows;
}

/**
 * Return true if the replacement has finished, false otherwise
 * @param {Object} state Current state
 * @param {Object} action Current action
 * @returns {Boolean}
 */
export function hasReplaceFinished( state, action ) {
	const replaceCount = action.results.rows + state.replaceCount;
	const total = getReplaceTotal( state, action );
	const { totals } = state;

	if ( action.progress.next === false || replaceCount >= total ) {
		return true;
	}

	if ( totals.matched_rows > 0 && replaceCount >= totals.matched_rows ) {
		return true;
	}

	return false;
}

/**
 * Has the search finished?
 * @param {Object} action Current action
 * @param {Array} results Array of results
 * @param {String} direction Current search direction
 * @returns {Boolean}
 */
export function isComplete( action, results, direction ) {
	// If going forward and no next, then complete
	if ( direction === SEARCH_FORWARD && action.progress.next === false ) {
		return true;
	}

	// If going back and no previous then complete
	if ( direction === SEARCH_BACKWARD && action.progress.previous === false ) {
		return true;
	}

	// If our results is greater than per page then stop
	if ( results.length >= action.perPage ) {
		return true;
	}

	return false;
}

/**
 * Is this an advanced (regex) search?
 * @param {Object} search Search object
 * @returns {Boolean}
 */
export const isAdvancedSearch = ( search ) => search.searchFlags.regex;

/**
 * Apply any processing to the search values to make them suitable for the API
 * @param {Object} values Search value object
 * @param {Object} sources Available sources
 * @returns {Object}
 */
export function getSearchValues( values, sources ) {
	const { source, searchFlags, sourceFlags, replacement } = values;

	return {
		...values,
		source: removePostTypes( source, sources ),
		searchFlags: Object.keys( searchFlags ),
		sourceFlags: Object.keys( sourceFlags ),
		replacement: getReplacement( replacement ),
	};
}

/**
 * In the client we represent 'delete' as a null value, but the API requires this to be an empty string
 * @param {String|null} replacement Replacement value, or `null` to remove
 * @returns String
 */
export function getReplacement( replacement ) {
	return replacement ? replacement : '';
}
