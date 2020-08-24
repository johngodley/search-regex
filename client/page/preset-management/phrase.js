/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

/**
 * Replace a tag within a phrase
 *
 * @param {string} phrase - Phrase to replace
 * @param {string} tag - Tag name
 * @param {string} title - Tag title
 */
function replaceTag( phrase, tag, title ) {
	const parts = phrase.split( tag );
	const replaced = [];

	for ( let index = 0; index < parts.length; index++ ) {
		if ( index % 2 !== 0 ) {
			replaced.push( <code title={ title } key={ `${ tag }-${ index }` }>{ tag }</code> );
		}

		replaced.push( parts[ index ] );
	}

	return replaced;
}

/**
 * Replace all the tags with a <code>tag</code> so it's visible
 *
 * @param {string} phrase - Phrase to replace
 * @param {PresetTag[]} tags - Array of tags
 */
function getPhraseWithTags( phrase, tags ) {
	let phraseParts = [ `${ phrase }` ];

	for ( let index = 0; index < tags.length; index++ ) {
		for ( let subIndex = 0; subIndex < phraseParts.length; subIndex++ ) {
			if ( typeof phraseParts[ subIndex ] === 'string' ) {
				phraseParts = phraseParts
					.slice( 0, subIndex )
					.concat(
						replaceTag( phraseParts[ subIndex ], tags[ index ].name, tags[ index ].title )
					)
					.concat( phraseParts.slice( subIndex + 1 ) );
			}
		}
	}

	return phraseParts;
}

/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */

/**
 * Preset search/replace phrase
 *
 * @param {object} props - Component props
 * @param {string|null} props.phrase - Search or replace phrase
 * @param {PresetTag[]} props.tags - Tags
 */
function Phrase( { phrase, tags } ) {
	if ( phrase === '' ) {
		return <em>{ __( 'no phrase' ) }</em>;
	}

	if ( phrase === null ) {
		return <em>{ __( 'remove phrase' ) }</em>;
	}

	return getPhraseWithTags( phrase, tags );
}

export default Phrase;
