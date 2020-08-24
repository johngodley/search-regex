/**
 * External dependencies
 */

import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import { STATUS_COMPLETE } from 'state/settings/type';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import { removePostTypes, getAllPostTypes } from 'lib/sources';
import { getPageUrl } from 'wp-plugin-lib/wordpress-url';
import { getDefaultPresetValues } from 'state/preset/selector';

/** @typedef {import('./type.js').SearchValues} SearchValues */
/** @typedef {import('./type.js').SearchSourceGroup} SearchSourceGroup */

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
export const getAvailablePerPage = () => [
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
	{
		value: 1000,
		label: __( '1000 per page' ),
	},
	{
		value: 2000,
		label: __( '2000 per page' ),
	},
];

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
 * Has the search finished?
 * @param {Object} action Current action
 * @param {} results Array of results
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

	return false;
}

/**
 * Is this an advanced (regex) search?
 *
 * @param {SearchValues} search Search object
 * @returns {Boolean}
 */
export const isAdvancedSearch = ( searchFlags ) => searchFlags.indexOf( 'regex' ) !== -1;

/**
 * Apply any processing to the search values to make them suitable for the API
 *
 * @param {SearchValues} values - Search value object
 * @param {SearchSourceGroup} sources - Available sources
 * @returns {SearchValues}
 */
export function getSearchValues( values, sources ) {
	return {
		...values,
		source: removePostTypes( values.source, sources ),
		replacement: getReplacement( values.replacement ),
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

/**
 * If the 'posts' type is present then ensure all post types are also included
 *
 * @param {string[]} sources - Array of source names
 * @param {*} allPostTypes
 * @returns {string[]}
 */
function addPostTypes( sources, allPostTypes ) {
	if ( sources.indexOf( 'posts' ) !== -1 ) {
		return sources.filter( ( item ) => allPostTypes.indexOf( item ) === -1 ).concat( allPostTypes );
	}

	return sources;
}

/**
 * Get default search values
 *
 * @returns {SearchValues}
 */
export function getDefaultSearch() {
	return {
		searchPhrase: '',
		searchFlags: [ 'case' ],
		source: [ 'post', 'page' ],
		sourceFlags: [],
		replacement: '',
		perPage: 25,
	};
}

/**
 * Get search values from query parameters
 *
 * @param {SearchSourceGroup} availableSources
 * @param {object|null} [queryParams] - Optional query object, otherwise uses browser URL
 * @returns {SearchValues}
 */
export function getQuerySearchParams( availableSources, queryParams = null ) {
	const query = queryParams ? queryParams : getPageUrl();
	const search = {};
	const params = {
		searchphrase: 'searchPhrase',
		searchflags: 'searchFlags',
		sourceflags: 'sourceFlags',
		source: 'source',
		replacement: 'replacement',
		perpage: 'perPage',
	};

	// Copy any query params across
	Object.keys( params ).forEach( ( key ) => {
		if ( query[ key ] ) {
			search[ params[ key ] ] = query[ key ];
		}
	} );

	// Sanitize the sources
	if ( search.source ) {
		search.source = addPostTypes( search.source, getAllPostTypes( availableSources ) );
	}

	return search;
}

export function getSearchFromPreset( preset ) {
	if ( preset ) {
		return {
			...preset.search,
			...getDefaultPresetValues( preset ),
		};
	}

	return getDefaultSearch();
}
