/**
 * External dependencies
 */

import React, { useRef, useEffect } from 'react';
import { connect } from 'react-redux';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */
import { Form, Table } from 'wp-plugin-components';
import Replace from 'component/replace';
import { getPreset } from 'state/preset/selector';

/**
 * A replacement dialog
 *
 * @param {string|React} props.description - Description string
 */
function ReplaceForm( props ) {
	const { preset, placeholder, setReplace, replace, canReplace, onCancel, description, onSave, className } = props;
		const ref = useRef( null );

	const replaceComponent = (
		<Replace
			disabled={ ! canReplace }
			preset={ preset }
			setReplace={ setReplace }
			replace={ replace }
			placeholder={ placeholder }
		/>
	);

	// Focus on the first input box
	useEffect(() => {
		setTimeout( () => {
			if ( ref.current ) {
				const first = ref.current.querySelector( 'input[type=text]' );

				if ( first ) {
					first.focus();
				}
			}
		}, 50 );
	}, [ ref ]);

	return (
		<div ref={ ref }>
			<Form onSubmit={ () => onSave( replace ) } className={ className }>
				{ preset ? <Table>{ replaceComponent }</Table> : replaceComponent }

				<div className="searchregex-replace__action">
					<p>{ description }</p>

					<p className="searchregex-replace__actions">
						<input
							type="submit"
							className="button button-primary"
							value={ __( 'Replace' ) }
							disabled={ replace === '' }
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
	null
)( ReplaceForm );
