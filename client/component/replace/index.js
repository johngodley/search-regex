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

function Replace( { canReplace, setReplace, className, autoFocus, onSave, onCancel, placeholder, description } ) {
	const ref = useRef( null );
	const [ replace, setLocalReplace ] = useState( '' );
	const [ replaceFlag, setReplaceFlag ] = useState( '' );

	const value = {
		id: 'replace',
		value: replace === null ? '' : replace,
		name: 'replace',
		onChange: ( ev ) => setLocalReplace( ev.target.value ),
		disabled: ! canReplace || replaceFlag === 'remove',
		placeholder: replaceFlag === 'remove' ? __( 'Search phrase will be removed' ) : placeholder,
		ref,
	};

	useEffect( () => {
		setLocalReplace( replaceFlag === 'remove' ?  null : '' );
		setReplace( replaceFlag === 'remove' ?  null : '' );
	}, [ replaceFlag ] );

	useEffect( () => {
		setReplace( replace );
	}, [ replace ] );

	// Autofocus
	useEffect( () => {
		setTimeout( () => {
			autoFocus && ref && ref.current.focus();
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
