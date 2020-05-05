/**
 * External dependencies
 */

import React, { useState, useEffect } from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import RestrictedMatches from 'component/result/restricted-matches';
import Replacement from './replacement';
import ReplacementPhrase from './replacement-phrase';
import { replaceRow } from 'state/search/action';
import { regexReplace, getMatchReplacement, getTypeOfReplacement } from './highlight-tools';
import './style.scss';

const resetMatches = ( matches ) => matches.map( () => '' );

function replaceWord( current, index, word ) {
	const clone = [ ...current ];

	clone[ index ] = word;

	return clone;
}

function getMatches( props, specific, setSpecific ) {
	const { source, matches, contextReplacement, columnId, rowId, onReplace, isReplacing } = props;
	const display = [];
	let offset = 0;

	for ( let index = 0; index < matches.length; index++ ) {
		const { context_offset, match, pos_id, replacement, captures } = matches[ index ];
		const matchedWord = getMatchReplacement( [ specific[ index ], contextReplacement, replacement ], match );

		display.push( source.substring( offset, context_offset ) );
		display.push(
			<Replacement
				typeOfReplacement={ getTypeOfReplacement( matchedWord, match ) }
				onSave={ ( phrase ) => onReplace( phrase, rowId, columnId, pos_id ) }
				onUpdate={ ( update ) => setSpecific( replaceWord( specific, index, update ) ) }
				isReplacing={ isReplacing }
				title={ match }
				key={ index }
			>
				<ReplacementPhrase match={ regexReplace( matchedWord, captures ) } originalMatch={ match } />
			</Replacement>
		);

		offset = context_offset + match.length;
	}

	display.push( source.substr( offset ) );

	return display;
}

function HighlightMatches( props ) {
	const { matches, count, contextReplacement } = props;
	const [ specific, setSpecific ] = useState( resetMatches( matches ) );
	const display = getMatches( props, specific, setSpecific );

	useEffect( () => {
		setSpecific( resetMatches( matches ) );
	}, [ contextReplacement ] );

	return (
		<div className="searchregex-match__context">
			{ display }

			{ matches.length !== count && <RestrictedMatches /> }
		</div>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onReplace: ( replacedPhrase, rowId, columnId, pos_id ) => {
			dispatch( replaceRow( replacedPhrase, rowId, columnId, pos_id ) );
		},
	};
}

export default connect(
	null,
	mapDispatchToProps
)( HighlightMatches );
