import ContextType from '../context-type';
import type { ResultColumn, SchemaColumn } from '../../../types/search';

interface ContextValueProps {
	context: any;
	rowId: string | number;
	column: ResultColumn;
	schema: SchemaColumn;
	setReplacement: ( value: any ) => void;
	className?: string;
}

function ContextValue( { context, rowId, column, schema, setReplacement, className }: ContextValueProps ): JSX.Element {
	if ( context.type === 'keyvalue' ) {
		return (
			<>
				<ContextValue
					rowId={ rowId }
					column={ column }
					schema={ schema }
					setReplacement={ setReplacement }
					context={ context.key }
					className="searchregex-list__key"
				/>
				=
				<ContextValue
					rowId={ rowId }
					column={ column }
					schema={ schema }
					setReplacement={ setReplacement }
					context={ context.value }
					className="searchregex-list__value"
				/>
			</>
		);
	}

	return (
		<>
			<ContextType
				context={ context }
				rowId={ rowId }
				column={ column }
				schema={ schema }
				setReplacement={ setReplacement }
				className={ className }
			/>
		</>
	);
}

export default ContextValue;
