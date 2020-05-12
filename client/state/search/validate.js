/**
 * Internal dependencies
 */

import { getSearchOptions, getPerPage } from './selector';
import getPreload from 'lib/preload';

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
 * @param {Array} sourceArray Valid sources
 * @param {Array} array Sources to sanitize
 */
function sanitizeSources( sourceArray, array ) {
	let sources = [];

	sourceArray.forEach( source => {
		sources = sources.concat( source.sources.map( item => item.name ) );
	} );

	return array.filter( ( source ) => sources.indexOf( source ) !== -1 );
}

/**
 * Sanitize source flags
 * @param {Array} source Array of sources
 * @param {Array} sourceFlags Array of valid source flags
 * @param {Array} flags Array of flags to sanitize
 */
function sanitizeSourceFlags( source, sourceFlags, flags ) {
	let remainingFlags = [ ...flags ];

	for ( let index = 0; index < source.length; index++ ) {
		const current = source[ index ];
		const flagsForCurrent = Object.keys( sourceFlags[ current ] );

		// Remove flags that existing in this source
		remainingFlags = remainingFlags.filter( flag => flagsForCurrent.indexOf( flag ) === -1 );
	}

	// Remove any flags not in one of the sources
	return flags.filter( item => remainingFlags.indexOf( item ) === -1 );
}

/**
 * Convert an array of strings to an object of string => true
 * @param {Array} array
 * @returns Object
 */
function arrayToObject( array ) {
	const obj = {};

	for ( let index = 0; index < array.length; index++)  {
		obj[ array[ index ] ] = true;
	}

	return obj;
}

/**
 * Validate the search object
 * @param {object} search Search object
 * @param {object|null} initialSources Source information
 */
export default function getValidatedSearch( search, initialSources = null, initialFlags = null ) {
	const sources = initialSources ? initialSources : getPreload( 'sources', [] );
	const flags = initialFlags ? initialFlags : getPreload( 'source_flags', [] );
	const { searchPhrase, searchFlags, sourceFlags, replacement, perPage } = search;
	const source = sanitizeSources( sources, search.source.length > 0 ? search.source : [] );

	return {
		searchPhrase,
		searchFlags: arrayToObject( sanitizeArray( searchFlags, getSearchOptions() ) ),

		source,
		sourceFlags: arrayToObject( sanitizeSourceFlags( source, flags, sourceFlags ) ),

		replacement,

		perPage: sanitizeValue( parseInt( perPage, 10 ), getPerPage(), 25 ),
	}
}
