import { getSearchOptions, getPerPage } from './selector';

function sanitizeValue( value, array, defaultValue ) {
	if ( array.find( ( item ) => item.value === value || item.name === value ) ) {
		return value;
	}

	return defaultValue;
}

function sanitizeArray( valueArray, array ) {
	return valueArray.filter( ( item ) => sanitizeValue( item, array, false ) );
}

function sanitizeSources( sourceArray, array ) {
	let sources = [];

	sourceArray.forEach( source => {
		sources = sources.concat( source.sources.map( item => item.name ) );
	} );

	return array.filter( ( source ) => sources.indexOf( source ) !== -1 );
}

function sanitizeSourceFlags( source, sourceFlags, flags ) {
	let remainingFlags = [ ...flags ];

	for ( let index = 0; index < source.length; index++ ) {
		const current = source[ index ];

		// Remove flags that existing in this source
		remainingFlags = remainingFlags.filter( flag => Object.keys( sourceFlags[ current ] ).indexOf( flag ) !== -1 );
	}

	// Remove any flags not in one of the sources
	return flags.filter( item => remainingFlags.indexOf( item ) === -1 );
}

function arrayToObject( array ) {
	const obj = {};

	for ( let index = 0; index < array.length; index++)  {
		obj[ array[ index ] ] = true;
	}

	return obj;
}

export default function validateSearch( search ) {
	const { searchPhrase, searchFlags, sourceFlags, replacement, perPage } = search;
	const source = sanitizeSources( SearchRegexi10n.preload.sources, search.source.length > 0 ? search.source : [] );

	return {
		searchPhrase,
		searchFlags: arrayToObject( sanitizeArray( searchFlags, getSearchOptions() ) ),

		source,
		sourceFlags: arrayToObject( sanitizeSourceFlags( source, SearchRegexi10n.preload.source_flags, sourceFlags ) ),

		replacement,

		perPage: sanitizeValue( parseInt( perPage, 10 ), getPerPage(), 25 ),
	}
}
