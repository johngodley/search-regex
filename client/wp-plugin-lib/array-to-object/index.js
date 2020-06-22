/**
 * Convert an array of strings to an object of string => true
 * @param {Array} array
 * @returns Object
 */
export default function arrayToObject( array ) {
	const obj = {};

	for ( let index = 0; index < array.length; index++ ) {
		obj[ array[ index ] ] = true;
	}

	return obj;
}
