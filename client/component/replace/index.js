/**
 * External dependencies
 */

import React, { useState, useEffect } from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import { Select } from 'wp-plugin-components';
import './style.scss';
import TaggedPhrases from 'component/tagged-phrase';

/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */

/**
 * @callback SaveCallback
 * @param {string} phrase
 */

/**
 * @callback CancelCallback
 */

const getReplaceOptions = () => [
	{
		value: 'single',
		label: __( 'Single' ),
	},
	{
		value: 'multi',
		label: __( 'Multi' ),
	},
	{
		value: 'remove',
		label: __( 'Remove' ),
	},
];

function getReplaceFlag( replace ) {
	if ( replace === null ) {
		return 'remove';
	} else if ( replace.indexOf( '\n' ) !== -1 ) {
		return 'multi';
	}

	return '';
}

/**
 * A replacement dialog
 *
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Whether we can replace this
 * @param {string} props.className - Class
 * @param {string|React} props.placeholder - Placeholder string
 * @param {SaveCallback} props.setReplace - Change the replacement string
 * @param {string} props.replace - Replacement string
 * @param {?PresetValue} [props.preset]
 */
function Replace( props ) {
	const {
		disabled,
		setReplace,
		className,
		placeholder,
		replace,
		preset = null,
	} = props;
	const [ replaceFlag, setReplaceFlag ] = useState( getReplaceFlag( replace ) );

	const value = {
		id: 'replace',
		value: replace === null ? '' : replace,
		name: 'replace',
		onChange: ( ev ) => setReplace( ev.target.value ),
		disabled: disabled || replaceFlag === 'remove',
		placeholder: replaceFlag === 'remove' ? __( 'Search phrase will be removed' ) : placeholder,
	};

	/** @param {string} replaceFlag */
	function changeReplace( replaceFlag ) {
		setReplaceFlag( replaceFlag );

		if ( replaceFlag !== '' ) {
			setReplace( replaceFlag === 'remove' ? null : replace );
		}
	}

	useEffect(() => {
		const flag = getReplaceFlag( replace );

		if ( replace === '' && replaceFlag === 'multi' && flag === '' ) {
			return;
		}
	}, [ replace ]);

	if ( preset && preset.tags.length > 0 ) {
		return (
			<TaggedPhrases
				disabled={ disabled }
				preset={ preset }
				phrase={ preset.search.replacement }
				onChange={ setReplace }
				className={ className }
				key={ preset.id }
			/>
		);
	}

	return (
		<div className="searchregex-replace__input">
			{ replaceFlag === 'multi' ? <textarea rows={ 4 } { ...value } /> : <input type="text" { ...value } /> }

			<Select
				items={ getReplaceOptions() }
				name="replace_flags"
				value={ replaceFlag }
				onChange={ ( ev ) => changeReplace( ev.target.value ) }
				disabled={ disabled }
			/>
		</div>
	);
}

export default Replace;
