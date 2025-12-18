import { useState } from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wp-plugin-components';
import ReplaceForm from '../replace-form';
import ReplacementPhrase from './replacement-phrase';
import { regexReplace, getMatchReplacement, getTypeOfReplacement } from './highlight-tools';
import { getNewActionFromResult } from '../../state/search/selector';
import type { Schema } from '../../types/search';

interface SpecificReplacement {
	replaceValue: string;
	operation: string;
	[ key: string ]: unknown;
}

interface ReplacementProps {
	match: string;
	captures: string[];
	replacement: string;
	rowId: number;
	canReplace: boolean;
	column: string;
	schema: Schema;
	onSave: ( value: SpecificReplacement ) => void;
}

/**
 * A highlighted replacement phrase with dropdown toggle
 * @param props
 */
function Replacement( props: ReplacementProps ): JSX.Element {
	const { match, captures, replacement, rowId, canReplace, column, schema, onSave } = props;
	const [ specific, setSpecific ] = useState< SpecificReplacement | null >( null );
	const matchedPhrase =
		getMatchReplacement( [ specific ? specific.replaceValue : null, replacement, match ] ) || match;
	const typeOfReplacement = getTypeOfReplacement( matchedPhrase, match );

	const reset = ( toggle?: () => void ): void => {
		setSpecific( null );
		if ( toggle ) {
			toggle();
		}
	};

	function toggleIt( toggle: () => void ): void {
		const schemaColumn = schema.columns.find( ( col ) => col.column === column );
		setSpecific( {
			...getNewActionFromResult( column as any, schemaColumn || null, schema.source || '' ),
			replaceValue: match,
			operation: 'replace',
		} );
		toggle();
	}

	function save( toggle: () => void ): void {
		if ( specific ) {
			onSave( specific );
		}
		reset( toggle );
	}

	return (
		<Dropdown
			renderToggle={ ( _isOpen: boolean, toggle: () => void ) => (
				<button
					onClick={ () => toggleIt( toggle ) }
					title={ __( 'Click to replace match', 'search-regex' ) }
					className={ classnames( {
						'searchregex-result__replaced': typeOfReplacement === 'replace',
						'searchregex-result__highlight': typeOfReplacement === 'match',
						'searchregex-result__deleted': typeOfReplacement === 'delete',
					} ) }
					type="button"
				>
					<ReplacementPhrase
						match={
							matchedPhrase
								? regexReplace( matchedPhrase === '' ? match : matchedPhrase, captures )
								: null
						}
						originalMatch={ match }
					/>
				</button>
			) }
			hasArrow
			onClose={ reset }
			align="centre"
			valign="bottom"
			renderContent={ ( toggle: () => void ) => {
				const schemaColumn = schema.columns.find( ( col ) => col.column === column );
				return (
					<ReplaceForm
						setReplacement={ ( details: unknown ) =>
							setSpecific( {
								...( ( specific || {} ) as SpecificReplacement ),
								...( details as Partial< SpecificReplacement > ),
							} )
						}
						replacement={ specific }
						canReplace={ canReplace }
						onCancel={ () => reset( toggle ) }
						onSave={ () => save( toggle ) }
						column={
							{
								column_id: schemaColumn?.column || column,
								column_label: schemaColumn?.column || column,
							} as any
						}
						schema={ schemaColumn || { type: 'string' as const } }
						rowId={ String( rowId ) }
						context={ { value: match, type: 'string', forceSingle: true } }
						className="searchregex-replace__modal"
						source={ schema.source || '' }
					/>
				);
			} }
		/>
	);
}

export default Replacement;
