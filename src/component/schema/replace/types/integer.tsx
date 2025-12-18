import ModifyInteger from '../../modify/types/integer';
import type { SchemaColumn, ResultColumn, ModifyIntegerColumn } from '../../../../types/search';

interface ReplaceIntegerProps {
	schema: SchemaColumn;
	replacement: unknown;
	disabled: boolean;
	setReplacement: ( values: unknown ) => void;
	fetchData: ( value: string ) => Promise< unknown >;
	column: ResultColumn;
	context: unknown;
	loadColumn: () => Promise< unknown >;
}

export default function ReplaceInteger( {
	schema,
	replacement,
	disabled,
	setReplacement,
	fetchData,
}: ReplaceIntegerProps ): JSX.Element {
	return (
		<ModifyInteger
			schema={ schema }
			disabled={ disabled }
			item={ replacement as ModifyIntegerColumn }
			fixOperation="set"
			onChange={ setReplacement }
			fetchData={ fetchData }
		/>
	);
}
