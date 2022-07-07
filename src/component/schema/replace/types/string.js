/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { Placeholder } from '@wp-plugin-components';

function isMultiple( context ) {
	if ( context.hasMultiple ) {
		return true;
	}

	return context.matches !== undefined;
}

function needsFullData( context ) {
	if ( context.type === 'string' ) {
		return false;
	}

	if ( context.value.length === context.value_length ) {
		return false;
	}

	return true;
}

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('../state/search/type').SchemaColumn} props.schema
 * @param {import('../state/search/type').ResultColumn} props.column
 * @param {import('../state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 **/
function ReplaceString( props ) {
	const { schema, replacement, setReplacement, context, loadColumn } = props;
	const {
		replaceValue = context.value,
		matchesOnly = false,
		operation,
		hasMultiple = isMultiple( context ),
	} = replacement;
	const [ loading, setLoading ] = useState( needsFullData( context ) );
	const [ loadValue, setLoadValue ] = useState( context.value );

	useEffect(() => {
		if ( ! needsFullData( context ) ) {
			return;
		}

		setLoading( true );

		loadColumn().then( ( data ) => {
			setReplacement( { replaceValue: data.value, originalValue: data.value } );
			setLoadValue( data.value );
			setLoading( false );
		} );
	}, []);

	useEffect(() => {
		if ( loading ) {
			return;
		}

		if ( matchesOnly ) {
			setReplacement( { replaceValue: context.search, searchValue: context.search } );
		} else {
			setReplacement( { replaceValue: loadValue || context.value } );
		}
	}, [ matchesOnly ]);

	if ( loading ) {
		return <Placeholder />;
	}

	return (
		<>
			<div className="searchregex-modify__string__row">
				{ schema.multiline && ! context.forceSingle && ! matchesOnly ? (
					<textarea
						value={ replaceValue || '' }
						onChange={ ( ev ) => setReplacement( { replaceValue: ev.target.value } ) }
					/>
				) : (
					<input
						type="text"
						value={ replaceValue || '' }
						onChange={ ( ev ) => setReplacement( { replaceValue: ev.target.value } ) }
						placeholder={ __( 'Enter replacement' ) }
					/>
				) }
			</div>
			{ hasMultiple && (
				<p className="searchregex-modify__string__row">
					<label>
						<input
							type="checkbox"
							value={ matchesOnly }
							onChange={ ( ev ) =>
								setReplacement( {
									matchesOnly: ev.target.checked,
									operation: ev.target.checked ? 'replace' : 'set',
									searchValue: ev.target.checked ? context.search : null,
									searchFlags: ev.target.checked ? context.flags : null,
									replaceValue: ev.target.checked ? context.search : loadValue,
								} )
							}
						/>{' '}
						{ __( 'Apply to matches only' ) }
					</label>
				</p>
			) }
		</>
	);
}

export default ReplaceString;
