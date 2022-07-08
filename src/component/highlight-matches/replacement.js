/**
 * External dependencies
 */

import { useState } from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { Dropdown } from '@wp-plugin-components';
import ReplaceForm from '../replace-form';
import ReplacementPhrase from './replacement-phrase';
import { regexReplace, getMatchReplacement, getTypeOfReplacement } from './highlight-tools';
import { getNewActionFromResult } from '../../state/search/selector';

/**
 * @callback SaveCallback
 * @param {string} phrase
 */

/**
 * @callback UpdateCallback
 * @param {string} phrase
 */

/**
 * A highlighted replacement phrase with dropdown toggle
 *
 * @param {object} props - Component props
 * @param {string} props.typeOfReplacement - Replacement type
 * @param {boolean} props.isReplacing - Are we replacing?
 * @param {object} props.children - Child
 * @param {SaveCallback} props.onSave - Save callback
 * @param {string} props.match - Match string
 * @param {string} props.replacement - Replacement string
 * @param {string[]} props.captures - Captured data
 */
function Replacement( props ) {
	const { match, captures, replacement, rowId, canReplace, column, schema, onSave } = props;
	const [ specific, setSpecific ] = useState( null );
	const matchedPhrase = getMatchReplacement( [ specific ? specific.replaceValue : null, replacement, match ] );
	const typeOfReplacement = getTypeOfReplacement( matchedPhrase, match );

	const reset = ( toggle ) => {
		setSpecific( null );
		toggle && toggle();
	};

	function toggleIt( toggle ) {
		setSpecific( {
			...getNewActionFromResult( column, schema, schema.source ),
			replaceValue: match,
			operation: 'replace',
		} );
		toggle();
	}

	function save( toggle ) {
		onSave( specific );
		reset( toggle );
	}

	return (
		<Dropdown
			renderToggle={ ( isOpen, toggle ) => (
				<span
					onClick={ () => toggleIt( toggle ) }
					title={ __( 'Click to replace match', 'search-regex' ) }
					className={ classnames( {
						'searchregex-result__replaced': typeOfReplacement === 'replace',
						'searchregex-result__highlight': typeOfReplacement === 'match',
						'searchregex-result__deleted': typeOfReplacement === 'delete',
					} ) }
				>
					<ReplacementPhrase match={ regexReplace( matchedPhrase === '' ? match : matchedPhrase, captures ) } originalMatch={ match } />
				</span>
			) }
			hasArrow
			onClose={ reset }
			align="centre"
			valign="bottom"
			renderContent={ ( toggle ) => (
				<ReplaceForm
					setReplacement={ ( details ) => setSpecific( { ...specific, ...details } ) }
					replacement={ specific }
					canReplace={ canReplace }
					onCancel={ () => reset( toggle ) }
					onSave={ () => save( toggle ) }
					column={ column }
					schema={ schema }
					rowId={ rowId }
					context={ { value: match, type: 'string', forceSingle: true } }
					className="searchregex-replace__modal"
				/>
			) }
		/>
	);
}

export default Replacement;
