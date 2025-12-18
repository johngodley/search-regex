import { useEffect, useRef, useState, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wp-plugin-components';

interface ContextWithMatches {
	value: string;
	value_length?: number;
	type?: string;
	matches?: unknown[];
	hasMultiple?: boolean;
	search?: string;
	flags?: string[];
	forceSingle?: boolean;
}

interface ReplaceStringReplacement {
	replaceValue?: string;
	matchesOnly?: boolean;
	operation?: string;
	hasMultiple?: boolean;
	searchValue?: string | null;
	searchFlags?: string[] | null;
	originalValue?: string;
}

interface ReplaceStringProps {
	schema: { multiline?: boolean; type?: string };
	replacement: ReplaceStringReplacement;
	setReplacement: ( values: Partial< ReplaceStringReplacement > ) => void;
	context: ContextWithMatches;
	loadColumn: () => Promise< { value: string } >;
	disabled?: boolean;
	column?: unknown;
	fetchData?: ( value: string ) => Promise< unknown >;
}

function isMultiple( context: ContextWithMatches ): boolean {
	if ( context.hasMultiple ) {
		return true;
	}

	return context.matches !== undefined;
}

function needsFullData( context: ContextWithMatches ): boolean {
	if ( context.type === 'string' ) {
		return false;
	}

	if ( context.value.length === context.value_length ) {
		return false;
	}

	return true;
}

export default function ReplaceString( {
	schema,
	replacement,
	setReplacement,
	context,
	loadColumn,
}: ReplaceStringProps ): JSX.Element {
	const { replaceValue = context.value, matchesOnly = false, hasMultiple = isMultiple( context ) } = replacement;
	const [ loading, setLoading ] = useState( needsFullData( context ) );
	const [ loadValue, setLoadValue ] = useState( context.value );
	const matchesOnlyId = useRef( `matches-only-${ Math.random().toString( 36 ).slice( 2 ) }` );

	useEffect( () => {
		if ( ! needsFullData( context ) ) {
			return;
		}

		setLoading( true );

		loadColumn().then( ( data ) => {
			setReplacement( { replaceValue: data.value, originalValue: data.value } );
			setLoadValue( data.value );
			setLoading( false );
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ context.type, context.value, context.value_length ] );

	useEffect( () => {
		if ( loading ) {
			return;
		}

		if ( matchesOnly ) {
			setReplacement( { replaceValue: context.search, searchValue: context.search } );
		} else {
			setReplacement( { replaceValue: loadValue || context.value } );
		}
	}, [ matchesOnly, loading, context.search, context.flags, context.value, loadValue, setReplacement ] );

	if ( loading ) {
		return <Placeholder />;
	}

	return (
		<>
			<div className="searchregex-modify__string__row">
				{ schema.multiline && ! context.forceSingle && ! matchesOnly ? (
					<textarea
						value={ replaceValue || '' }
						onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
							setReplacement( { replaceValue: ev.target.value } )
						}
					/>
				) : (
					<input
						type="text"
						value={ replaceValue || '' }
						onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
							setReplacement( { replaceValue: ev.target.value } )
						}
						placeholder={ __( 'Enter replacement', 'search-regex' ) }
					/>
				) }
			</div>
			{ hasMultiple && (
				<p className="searchregex-modify__string__row">
					<label htmlFor={ matchesOnlyId.current }>
						<input
							id={ matchesOnlyId.current }
							type="checkbox"
							value={ matchesOnly ? 'true' : 'false' }
							onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
								setReplacement( {
									matchesOnly: ev.target.checked,
									operation: ev.target.checked ? 'replace' : 'set',
									searchValue: ev.target.checked ? context.search : null,
									searchFlags: ev.target.checked ? context.flags : null,
									replaceValue: ev.target.checked ? context.search : loadValue,
								} )
							}
						/>{ ' ' }
						{ __( 'Apply to matches only', 'search-regex' ) }
					</label>
				</p>
			) }
		</>
	);
}
