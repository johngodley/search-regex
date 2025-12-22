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
	// Use replacement.replaceValue if it exists, otherwise use context.value as default
	// Use explicit check to avoid resetting user input
	// If replacement is null/undefined or replaceValue is not set, use context.value
	// But once user starts typing, replacement.replaceValue will exist and we'll use that
	const replaceValue =
		replacement &&
		typeof replacement === 'object' &&
		'replaceValue' in replacement &&
		replacement.replaceValue !== undefined
			? replacement.replaceValue
			: context.value;
	const matchesOnly = replacement?.matchesOnly ?? false;
	const hasMultiple = replacement?.hasMultiple ?? isMultiple( context );
	const [ loading, setLoading ] = useState( needsFullData( context ) );
	const [ loadValue, setLoadValue ] = useState( context.value );
	const previousMatchesOnlyRef = useRef< boolean | undefined >( undefined );
	const initializedRef = useRef( false );
	const dataLoadedRef = useRef( false );
	const matchesOnlyId = useRef( `matches-only-${ Math.random().toString( 36 ).slice( 2 ) }` );

	// Only load full data once when component mounts, not on every render
	useEffect( () => {
		if ( ! needsFullData( context ) ) {
			return;
		}

		// Only load if we haven't loaded yet
		if ( dataLoadedRef.current ) {
			return;
		}

		dataLoadedRef.current = true;
		setLoading( true );

		loadColumn().then( ( data ) => {
			setReplacement( { replaceValue: data.value, originalValue: data.value } );
			setLoadValue( data.value );
			setLoading( false );
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] ); // Empty deps - only run once on mount

	// Initialize replaceValue only once on mount, before user starts typing
	useEffect( () => {
		if ( loading ) {
			return;
		}

		// Only initialize once - use a flag to prevent re-initialization
		if ( initializedRef.current ) {
			return;
		}

		// Check if replaceValue has already been set (e.g., by user typing or previous initialization)
		const hasReplaceValue =
			replacement &&
			typeof replacement === 'object' &&
			'replaceValue' in replacement &&
			replacement.replaceValue !== undefined &&
			replacement.replaceValue !== '';

		// Only initialize if replaceValue hasn't been set yet
		if ( ! hasReplaceValue ) {
			initializedRef.current = true;

			if ( matchesOnly && context.search !== undefined ) {
				setReplacement( { replaceValue: context.search, searchValue: context.search } );
			} else {
				const value = loadValue || context.value;
				if ( value !== undefined ) {
					setReplacement( { replaceValue: value } );
				}
			}
		} else {
			// User has already set a value, mark as initialized to prevent overwriting
			initializedRef.current = true;
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ loading ] ); // Only depend on loading, not matchesOnly

	// Handle matchesOnly checkbox changes separately
	useEffect( () => {
		if ( loading ) {
			return;
		}

		const matchesOnlyChanged =
			previousMatchesOnlyRef.current !== undefined && previousMatchesOnlyRef.current !== matchesOnly;
		previousMatchesOnlyRef.current = matchesOnly;

		if ( matchesOnlyChanged ) {
			// User toggled the checkbox - update the value accordingly
			if ( matchesOnly ) {
				if ( context.search !== undefined ) {
					setReplacement( { replaceValue: context.search, searchValue: context.search } );
				}
			} else {
				const value = loadValue || context.value;
				if ( value !== undefined ) {
					setReplacement( { replaceValue: value } );
				}
			}
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ matchesOnly, loading ] );

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
							onChange={ ( ev: ChangeEvent< HTMLInputElement > ) => {
								const checked = ev.target.checked;
								const updates: {
									matchesOnly: boolean;
									operation: string;
									searchValue?: string | null;
									searchFlags?: string[] | null;
									replaceValue?: string;
								} = {
									matchesOnly: checked,
									operation: checked ? 'replace' : 'set',
								};
								if ( checked ) {
									if ( context.search !== undefined ) {
										updates.searchValue = context.search;
										updates.replaceValue = context.search;
									}
									if ( context.flags !== undefined ) {
										updates.searchFlags = context.flags;
									}
								} else {
									updates.searchValue = null;
									updates.searchFlags = null;
									if ( loadValue !== undefined ) {
										updates.replaceValue = loadValue;
									}
								}
								setReplacement( updates );
							} }
						/>{ ' ' }
						{ __( 'Apply to matches only', 'search-regex' ) }
					</label>
				</p>
			) }
		</>
	);
}
