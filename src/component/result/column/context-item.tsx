import type { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { hasValue } from '../modify-column';
import ColumnLabel from '../column-label';
import { Dropdown } from '@wp-plugin-components';
import ReplaceForm from '../../replace-form';
import getValueType from '../../value-type';
import type { ResultColumn, SchemaColumn, Schema } from '../../../types/search';
import { useSaveRow } from '../../../hooks/use-search';

interface ContextItemProps {
	column: ResultColumn;
	schema: SchemaColumn & { modify?: boolean };
	replacement: any;
	save: ( value: any ) => void;
	disabled: boolean;
	source: string;
	rowId: string | number;
	children?: ReactNode;
	context: any;
}

function ContextItem( props: ContextItemProps ): JSX.Element {
	const { column, schema, replacement, save, disabled, source, rowId, children, context } = props;
	const canReplace = hasValue( replacement, column, schema );
	const saveRowMutation = useSaveRow();

	function onSave( toggle: () => void ): void {
		toggle();
		save( null );
		saveRowMutation.mutate( { replacement, rowId: String( rowId ) } );
	}

	return (
		<div className="searchregex-match searchregex-match__list">
			<Dropdown
				renderToggle={ ( _isOpen, toggle ) => (
					<ColumnLabel
						column={ column }
						schema={ { name: '', type: '', columns: [ schema ] } as Schema }
						replacement={ replacement }
						setReplacement={ save }
						disabled={ disabled }
						source={ source }
						toggle={ toggle }
						canEdit={ schema.modify !== false }
						context={ context }
					/>
				) }
				hasArrow
				align="centre"
				valign="top"
				onClose={ () => save( null ) }
				renderContent={ ( toggle ) => (
					<ReplaceForm
						setReplacement={ save }
						replacement={ replacement }
						context={ context }
						canReplace={ canReplace }
						rowId={ String( rowId ) }
						onSave={ () => onSave( toggle ) }
						onCancel={ () => {
							save( null );
							toggle();
						} }
						column={ column }
						schema={ schema }
						source={ source }
						className="searchregex-replace__modal"
						description={
							getValueType( context.type === 'keyvalue' ? context.value.value_type : context.value_type )
								? __( 'Contains encoded data', 'search-regex' )
								: ''
						}
					/>
				) }
			/>

			{ children }
		</div>
	);
}

export default ContextItem;
