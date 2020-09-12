/**
 * Internal dependencies
 */
import { getAllPostTypes } from 'lib/sources';

/** @typedef {import('state/search/type').SearchSourceGroup} SearchSourceGroup */

/**
 * Ensure there is always at least one source selected
 *
 * @param {string[]} selected - Array of selected options
 * @returns {string[]}
 */
function ensureOneSource( selected ) {
	if ( selected.length === 0 ) {
		return [ 'post', 'page' ];
	}

	return selected;
}

/**
 * If 'all post types' is enabled, then disable all individual post types.
 *
 * If a specific post type is then disabled, enable all the other post types and disable 'all post types'.
 *
 * Default to 'post' and 'page' if no sources are picked
 *
 * @param {string[]} selected - Array of selected options
 * @param {string} optionValue - The changed option value
 * @param {SearchSourceGroup[]} sources - Array of SearchSourceGroup objects
 */
export function convertToSource( selected, optionValue, sources ) {
	const allPostTypes = getAllPostTypes( sources );
	const postTypesEnabled = allPostTypes.reduce(
		( prev, current ) => ( selected.indexOf( current ) !== -1 ? prev + 1 : prev ),
		0
	);

	// 'all post types' enabled - add all post types
	if ( optionValue === 'posts' && selected.indexOf( 'posts' ) !== -1 ) {
		return selected.filter( ( source ) => allPostTypes.indexOf( source ) === -1 ).concat( allPostTypes );
	}

	// 'all post types' disabled - disable all post types
	if ( optionValue === 'posts' && selected.indexOf( 'posts' ) === -1 ) {
		return ensureOneSource( selected.filter( ( source ) => allPostTypes.indexOf( source ) === -1 ) );
	}

	// A specific post type was checked and 'all post types' is enabled - disable 'all post types', and enable all post types except this one
	if ( allPostTypes.indexOf( optionValue ) !== -1 && selected.indexOf( 'posts' ) !== -1 ) {
		return (
			selected
				// Remove 'all post types'
				.filter( ( source ) => source !== 'posts' )
				// Remove the one that was just disabled
				.filter( ( source ) => source !== optionValue )
		);
	}

	// All individual post types were selected, so enable the 'all post types'
	if ( allPostTypes.length === postTypesEnabled ) {
		return selected.filter( ( source ) => source !== 'posts' ).concat( [ 'posts' ] );
	}

	// Just return what is selected
	return ensureOneSource( selected );
}

/**
 * Convert the sources object into a format suitable for the MultiGroupDropdown
 *
 * @param {SearchSourceGroup[]} sources - Array of SearchSourceGroup objects
 * @returns {MultiOptionGroupValue[]}
 */
export function getSourcesForDropdown( sources ) {
	return sources.map( ( sourceGroup ) => {
		return {
			label: sourceGroup.label,
			value: sourceGroup.name,
			options: sourceGroup.sources.map( ( { label, name } ) => {
				return {
					label,
					value: name,
				};
			} ),
		};
	} );
}

/**
 * Modify the source badges so if 'all post types' is enabled then we don't show each post type
 *
 * @param {string[]} badges - Array of badges
 * @param {SearchSourceGroup[]} sources - Array of SearchSourceGroup objects
 * @returns {string[]}
 */
export function customBadge( badges, sources ) {
	// If 'all post types' then dont show the post types
	if ( badges.indexOf( 'posts' ) !== -1 ) {
		const allPosts = getAllPostTypes( sources );

		return badges.filter( ( item ) => allPosts.indexOf( item ) === -1 );
	}

	return badges;
}

/**
 * Get all the source flags across all the selected sources
 *
 * @param {Object.<string,string>} options - Array of all the source options
 * @param {string[]} source - Selected sources
 * @returns {MultiOptionValue[]}
 */
export function getSourceFlagOptions( options, source ) {
	/** @type {MultiOptionValue[]} */
	let sourceFlags = [];

	Object.keys( options ).forEach( ( option ) => {
		if ( source.indexOf( option ) !== -1 ) {
			const newFlags = Object.keys( options[ option ] ).map( ( item ) => ( {
				label: options[ option ][ item ],
				value: item,
			} ) );

			for ( let index = 0; index < newFlags.length; index++ ) {
				if ( ! sourceFlags.find( ( item ) => item.value === newFlags[ index ].value ) ) {
					sourceFlags.push( newFlags[ index ] );
				}
			}
		}
	} );

	return sourceFlags;
}
