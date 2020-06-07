/**
 * External dependencies
 */

import React, { useState, useEffect, useRef } from 'react';
import { translate as __ } from 'wp-plugin-library/lib/locale';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { Select } from 'wp-plugin-library';
import './style.scss';

/**
 * @callback SaveCallback
 * @param {string} phrase
 */

/**
 * @callback CancelCallback
 */

const getReplaceOptions = () => [
	{
		value: '',
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

function ReplaceContainer( { children, onSave, className } ) {
	const classes = classnames( 'searchregex-replace', className );

	if ( onSave ) {
		const save = ( ev ) => {
			ev.preventDefault();
			onSave();
		};

		return (
			<form className={ classes } onSubmit={ save }>
				{ children }
			</form>
		)
	}

	return (
		<div className={ classes }>
			{ children }
		</div>
	);
}

/**
 *
 * @param {*} param0
 */

/**
 * A replacement dialog
 *
 * @param {object} props - Component props
 * @param {boolean} props.canReplace - Whether we can replace this
 * @param {string} props.className - Class
 * @param {boolean} props.autoFocus - Whether to autofocus on the popup
 * @param {string|React} props.placeholder - Placeholder string
 * @param {string|React} props.description - Description string
 * @param {SaveCallback} props.setReplace - Change the replacement string
 * @param {SaveCallback} props.onSave - Change the replacement string
 * @param {CancelCallback} props.onCancel - Change the replacement string
 * @param {string} props.replace - Replacement string
 * @returns React
 */
function Replace( { canReplace, setReplace, className, autoFocus, onSave, onCancel, placeholder, description, replace } ) {
	const ref = useRef( null );
	const [ replaceFlag, setReplaceFlag ] = useState( '' );

	const value = {
		id: 'replace',
		value: replace === null ? '' : replace,
		name: 'replace',
		onChange: ( ev ) => setReplace( ev.target.value ),
		disabled: ! canReplace || replaceFlag === 'remove',
		placeholder: replaceFlag === 'remove' ? __( 'Search phrase will be removed' ) : placeholder,
		ref,
	};

	// When replace flag is changed we reset the replace value
	useEffect( () => {
		setReplace( replaceFlag === 'remove' ? null : '' );
	}, [ replaceFlag ] );

	// Autofocus
	useEffect( () => {
		setTimeout( () => {
			autoFocus && ref?.current?.focus();
		}, 50 );
	}, [ ref ] );

	return (
		<ReplaceContainer onSave={ onSave && ( () => onSave( replace ) ) } className={ className }>
			<div className="searchregex-replace__input">
				{ replaceFlag === 'multi' ? (
					<textarea
						rows={ 4 }
						{ ...value }
					/>
				) : (
					<input
						type="text"
						{ ...value }
					/>
				) }

				<Select
					items={ getReplaceOptions() }
					name="replace_flags"
					value={ replaceFlag }
					onChange={ ( ev ) => setReplaceFlag( ev.target.value ) }
					disabled={ ! canReplace }
				/>
			</div>

			<div className="searchregex-replace__action">
				{ description && <p>{ description }</p> }

				{ onSave && (
					<p className="searchregex-replace__actions">
						<input
							type="submit"
							className="button button-primary"
							value={ __( 'Replace' ) }
							disabled={ replace === '' }
						/>
						<input type="button" className="button button-secondary" value={ __( 'Cancel' ) } onClick={ onCancel } />
					</p>
				) }
			</div>
		</ReplaceContainer>
	)
}

export default Replace;
