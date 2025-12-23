import ModifyMember from '../../modify/types/member';
import type { SchemaColumn, ResultColumn, ContextList, ModifyMemberColumn } from '../../../../types/search';

interface ReplaceMemberProps {
	schema: SchemaColumn;
	replacement: unknown;
	disabled: boolean;
	setReplacement: ( values: unknown ) => void;
	fetchData: ( value: string ) => Promise< unknown >;
	column: ResultColumn;
	context: unknown;
	loadColumn: () => Promise< unknown >;
}

export default function ReplaceMember( {
	schema,
	replacement,
	disabled,
	setReplacement,
	fetchData,
	column,
}: ReplaceMemberProps ): JSX.Element {
	return (
		<ModifyMember
			schema={ schema }
			disabled={ disabled }
			item={ replacement as ModifyMemberColumn }
			fixOperation="set"
			onChange={ setReplacement }
			fetchData={ fetchData }
			localLabels={ ( column.contexts as ContextList[] ).map( ( item ) => ( {
				value: column.column_id + '_' + item.value,
				label: item.value_label,
			} ) ) }
		/>
	);
}
