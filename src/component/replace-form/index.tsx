import React, { useRef, useEffect } from 'react';
import { connect } from 'react-redux';
import { __ } from '@wordpress/i18n';
import { Form } from '@wp-plugin-components';
import { getPreset } from '../../state/preset/selector';
import ReplaceColumn from '../schema/replace';
import type { PresetValue } from '../../types/preset';
import type { ResultColumn, SchemaColumn } from '../../types/search';
import type { SetReplace } from '../../state/search/type';
import './style.scss';

interface ContextValue {
	[ key: string ]: unknown;
}

interface ReplaceFormOwnProps {
	description?: string | React.ReactElement;
	canReplace: boolean;
	schema: SchemaColumn;
	column: ResultColumn;
	setReplacement: SetReplace;
	className?: string;
	replacement: object | null;
	context: ContextValue;
	onSave: ( replacement: object | null ) => void;
	source: string;
	onCancel: () => void;
	rowId: string;
}

interface ReplaceFormStateProps {
	preset?: PresetValue | null;
}

type ReplaceFormProps = ReplaceFormOwnProps & ReplaceFormStateProps;

interface RootState {
	preset: {
		presets: PresetValue[];
		currentPreset: string;
	};
}

function ReplaceForm( props: ReplaceFormProps ): JSX.Element {
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
	const ref = useRef< HTMLDivElement >( null );

	// Focus on the first input box
	useEffect( () => {
		setTimeout( () => {
			if ( ref.current ) {
				const first = ref.current.querySelector< HTMLInputElement | HTMLTextAreaElement >(
					'input[type=text],textarea'
				);

				if ( first ) {
					first.focus();
					first.select();
				}
			}
		}, 50 );
	}, [ ref ] );

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
							value={ __( 'Replace', 'search-regex' ) }
							disabled={ ! canReplace }
						/>
						<input
							type="button"
							className="button button-secondary"
							value={ __( 'Cancel', 'search-regex' ) }
							onClick={ onCancel }
						/>
					</p>
				</div>
			</Form>
		</div>
	);
}

function mapStateToProps( state: RootState ): ReplaceFormStateProps {
	const { presets, currentPreset } = state.preset;
	const preset = getPreset( presets, currentPreset );

	return {
		preset,
	};
}

export default connect< ReplaceFormStateProps, Record< string, never >, ReplaceFormOwnProps, RootState >(
	mapStateToProps,
	null
)( ReplaceForm );
