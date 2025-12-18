import ModifyDate from '../../modify/types/date';
import type { SchemaColumn, ResultColumn, ModifyDateColumn } from '../../../../types/search';

interface ReplaceDateProps {
	schema: SchemaColumn;
	replacement: unknown;
	disabled: boolean;
	setReplacement: ( values: unknown ) => void;
	column: ResultColumn;
	fetchData: ( value: string ) => Promise< unknown >;
	context: unknown;
	loadColumn: () => Promise< unknown >;
}

export default function ReplaceDate( {
	schema,
	replacement,
	disabled,
	setReplacement,
}: ReplaceDateProps ): JSX.Element {
	return (
		<ModifyDate
			schema={ schema }
			disabled={ disabled }
			item={ replacement as ModifyDateColumn }
			fixOperation="set"
			onChange={ setReplacement }
			fetchData={ () => Promise.resolve() }
		/>
	);
}
