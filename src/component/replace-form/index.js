/**
 * External dependencies
 */

import React, { useRef, useEffect } from 'react';
import { connect } from 'react-redux';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Form } from '@wp-plugin-components';
import { getPreset } from '../../state/preset/selector';
import ReplaceColumn from '../schema/replace';

/**
 * A replacement dialog
 *
 * @param {object} props - Component props
 * @param {string|React} props.description - Description string
 * @param {boolean} props.canReplace - Can the current replacement value be replaced?
 * @param {import('../state/search/type').Schema} props.schema
 * @param {import('../state/search/type').ResultColumn} props.column
 * @param {import('../state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {string} [props.className]
 * @param {object|null} props.replacement - Row replacement value
 */
function ReplaceForm( props ) {
	const {
		setReplacement,
		replacement,
		canReplace,
		context,
		onSave,
		source,
		description,
		className,
		column,
		schema,
		onCancel,
		rowId,
	} = props;
	const ref = useRef( null );

	// Focus on the first input box
	useEffect(() => {
		setTimeout( () => {
			if ( ref.current ) {
				const first = ref.current.querySelector( 'input[type=text],textarea' );

				if ( first ) {
					first.focus();
					first.select();
				}
			}
		}, 50 );
	}, [ ref ]);

	return (
		<div className="searchregex-replace__form" ref={ ref }>
			<Form onSubmit={ () => onSave( replacement ) } className={ className }>
				<ReplaceColumn
					schema={ schema }
					column={ column }
					disabled={ false }
					setReplacement={ setReplacement }
					replacement={ replacement }
					source={ source }
					context={ context }
					rowId={ rowId }
				/>

				<div className="searchregex-replace__action">
					{ description && <p>{ description }</p> }

					<p className="searchregex-replace__actions">
						<input
							type="submit"
							className="button button-primary"
							value={ __( 'Replace' ) }
							disabled={ ! canReplace }
						/>
						<input
							type="button"
							className="button button-secondary"
							value={ __( 'Cancel' ) }
							onClick={ onCancel }
						/>
					</p>
				</div>
			</Form>
		</div>
	);
}

function mapStateToProps( state ) {
	const { presets, currentPreset } = state.preset;
	const preset = getPreset( presets, currentPreset );

	return {
		preset,
	};
}

export default connect(
	mapStateToProps,
	null,//mapDispatchToProps
)( ReplaceForm );
