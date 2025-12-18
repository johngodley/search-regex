import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { getNewActionFromResult } from '../../state/search/selector';
import getValueType from '../value-type';
import type { ResultColumn, Schema, ModifyColumn } from '../../types/search';

interface ContextValue {
	value_type?: string;
	type?: string;
	value?: unknown;
	[ key: string ]: unknown;
}

interface ActionFromResult {
	column: string;
	operation: string;
	source: string;
	values?: string[];
	[ key: string ]: unknown;
}

interface ColumnLabelProps {
	column: ResultColumn;
	schema: Schema;
	setReplacement: ( value: ActionFromResult | ModifyColumn | null ) => void;
	replacement: ModifyColumn | null;
	toggle?: () => void;
	disabled: boolean;
	canEdit: boolean;
	source: string;
	context: ContextValue;
}

function ColumnLabel( props: ColumnLabelProps ): JSX.Element {
	const { column, schema, setReplacement, replacement, toggle, disabled, canEdit, source, context } = props;
	const { column_label: columnLabel } = column;
	const typedContext = context.type === 'keyvalue' ? ( context.value as ContextValue ) : context;
	const valueType = typedContext.value_type ? getValueType( typedContext.value_type ) : null;

	function enable(): void {
		if ( disabled || ! canEdit ) {
			return;
		}

		const schemaColumn = schema.columns.find( ( col ) => col.column === column.column_id );
		const newAction = getNewActionFromResult( column as any, schemaColumn || null, source );

		setReplacement( replacement === null ? newAction : null );
		if ( toggle ) {
			toggle();
		}
	}

	return (
		<button
			type="button"
			className={ classnames(
				'searchregex-match__column',
				'searchregex-match__column__' + context.type,
				disabled || ! canEdit ? 'searchregex-match__column__disabled' : null
			) }
			title={
				valueType
					? __(
							'This column contains special formatting. Modifying it could break the format.',
							'search-regex'
					  )
					: __( 'Click to replace column', 'search-regex' )
			}
			onClick={ enable }
		>
			{ columnLabel }
			{ valueType && <div className="searchregex-match__column__type">{ valueType }</div> }
		</button>
	);
}

export default ColumnLabel;
