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
	return sourceArray
		.map( ( source ) => sanitizeValue( array, source.sources ) )
		.filter( item => item );
}

function sanitizeSourceFlags( source, sourceFlags, array ) {
	if ( sourceFlags[ source[ 0 ] ] ) {
		return array.filter( ( item ) => sourceFlags[ source[ 0 ] ][ item ] !== undefined );
	}

	return [];
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
	const source = sanitizeSources( SearchRegexi10n.preload.sources, search.source.length > 0 ? search.source[ 0 ] : [] );

	return {
		searchPhrase,
		searchFlags: arrayToObject( sanitizeArray( searchFlags, getSearchOptions() ) ),

		source,
		sourceFlags: arrayToObject( sanitizeSourceFlags( source, SearchRegexi10n.preload.source_flags, sourceFlags ) ),

		replacement,

		perPage: sanitizeValue( parseInt( perPage, 10 ), getPerPage(), 25 ),
	}
}
