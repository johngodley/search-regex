import React, { useRef, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Form } from '@wp-plugin-components';
import ReplaceColumn from '../schema/replace';
import type { ResultColumn, SchemaColumn, SetReplace } from '../../types/search';
import './style.scss';

interface ContextValue {
	[ key: string ]: unknown;
}

interface ReplaceFormProps {
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
			<Form onSubmit={ () => onSave( replacement ) } { ...( className ? { className } : {} ) }>
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

export default ReplaceForm;
