/**
 * Internal dependencies
 */

import { getAvailableSearchFlags, getAvailablePerPage } from './selector';
import getPreload from 'lib/preload';
import { getSchema } from './selector';

/** @typedef {import('./type.js').SearchValues} SearchValues */
/** @typedef {import('./type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('./type.js').Filter} Filter */

function sanitizeValue( value, array, defaultValue ) {
	if ( array.find( ( item ) => item.value === value || item.name === value ) ) {
		return value;
	}

	return defaultValue;
}

/**
 * Sanitize an array to a list of valid values
 * @param {Arrau} valueArray Array of valid values
 * @param {*} array Array of values to sanitize
 */
function sanitizeArray( valueArray, array ) {
	return valueArray.filter( ( item ) => sanitizeValue( item, array, false ) );
}

/**
 * Sanitize sources
 * @param {SearchSourceGroup[]} sourceArray Valid sources
 * @param {string[]} array Sources to sanitize
 * @returns {string[]}
 */
function sanitizeSources( sourceArray, array ) {
	/**
	 * @type {string[]}
	 */
	let sources = [];

	sourceArray.forEach( ( source ) => {
		sources = sources.concat( source.sources.map( ( item ) => item.name ) );
	} );

	return array.filter( ( source ) => sources.indexOf( source ) !== -1 );
}

/**
 * Sanitize filters
 * @param {Filter[]} filters Filters
 * @returns {Filter[]}
 */
function sanitizeFilters( filters, schema, source ) {
	return filters.filter( ( filter ) => {
		const found = getSchema( schema, filter.type );

		if ( found && source.indexOf( found.type ) !== -1 ) {
			return ( filter.items.filter( ( itemFilter ) => {
				return found.columns.find( ( finding ) => finding.column === itemFilter.column ) !== undefined;
			} ).length = filter.items.length );
		}

		return false;
	} );
}

function sanitizeView( view, schema ) {
	if ( ! Array.isArray( view ) ) {
		return [];
	}

	return view.filter( ( item ) => {
		const parts = item.split( '__' );

		if ( parts.length === 2 ) {
			const found = getSchema( schema, parts[ 0 ] );

			if ( found ) {
				return found.columns.find( ( finding ) => finding.column === parts[ 1 ] ) !== undefined;
			}
		}

		return false;
	} );
}

/**
 * Validate the search object
 *
 * @param {SearchValues} search Search object
 * @param {object|null} initialSources Source information
 * @returns {SearchValues}
 */
export default function getValidatedSearch( search, initialSources = null, initialSchema = null ) {
	const sources = initialSources ? initialSources : getPreload( 'sources', [] );
	const schema = initialSchema ? initialSchema : getPreload( 'schema', [] );
	const { searchPhrase, searchFlags, replacement, perPage, view, action, actionOption } = search;
	const source = sanitizeSources( sources, search.source.length > 0 ? search.source : [] );

	return {
		searchPhrase,
		searchFlags: sanitizeArray( searchFlags, getAvailableSearchFlags() ),
		source,
		replacement,
		perPage: sanitizeValue( parseInt( perPage, 10 ), getAvailablePerPage(), 25 ),
		filters: sanitizeFilters( search.filters, schema, source ),
		view: sanitizeView( view, schema ),
		action,
		actionOption,
	};
}
