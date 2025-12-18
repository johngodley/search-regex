import { __ } from '@wordpress/i18n';
import { ReactNode } from 'react';
import type { PresetTag } from '../../types/preset';

function replaceTag( phrase: string, tag: string, title: string ): ReactNode[] {
	const parts = phrase.split( tag );
	const replaced: ReactNode[] = [];

	for ( let index = 0; index < parts.length; index++ ) {
		if ( index % 2 !== 0 ) {
			replaced.push(
				<code title={ title } key={ `${ tag }-${ index }` }>
					{ tag }
				</code>
			);
		}

		replaced.push( parts[ index ] );
	}

	return replaced;
}

function getPhraseWithTags( phrase: string, tags: PresetTag[] ): ReactNode[] {
	let phraseParts: ReactNode[] = [ `${ phrase }` ];

	for ( let index = 0; index < tags.length; index++ ) {
		const newPhraseParts: ReactNode[] = [];

		for ( let subIndex = 0; subIndex < phraseParts.length; subIndex++ ) {
			if ( typeof phraseParts[ subIndex ] === 'string' ) {
				const replacedTags = replaceTag(
					phraseParts[ subIndex ] as string,
					tags[ index ].name,
					tags[ index ].label
				);
				newPhraseParts.push( ...replacedTags );
			} else {
				newPhraseParts.push( phraseParts[ subIndex ] );
			}
		}

		void ( phraseParts = newPhraseParts );
	}

	return phraseParts;
}

interface PhraseProps {
	phrase: string | null;
	tags: PresetTag[];
}

function Phrase( { phrase, tags }: PhraseProps ) {
	if ( phrase === '' ) {
		return <em>{ __( 'no phrase', 'search-regex' ) }</em>;
	}

	if ( phrase === null ) {
		return <em>{ __( 'remove phrase', 'search-regex' ) }</em>;
	}

	return <>{ getPhraseWithTags( phrase, tags ) }</>;
}

export default Phrase;
