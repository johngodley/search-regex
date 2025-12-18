import ReplaceInteger from './integer';
import ReplaceDate from './date';
import ReplaceString from './string';
import ReplaceMember from './member';
import ReplaceKeyValue from './keyvalue';
import type { SchemaColumn, ResultColumn } from '../../../../types/search';

interface ReplaceTypeProps {
	disabled: boolean;
	schema: SchemaColumn;
	column: ResultColumn;
	fetchData: ( value: string ) => Promise< unknown >;
	context: unknown;
	setReplacement: ( values: unknown ) => void;
	replacement: unknown;
	loadColumn: () => Promise< unknown >;
}

function ReplaceType( props: ReplaceTypeProps ): JSX.Element | null {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <ReplaceInteger { ...props } />;
	}

	if ( type === 'date' ) {
		return <ReplaceDate { ...props } />;
	}

	if ( type === 'string' ) {
		return <ReplaceString { ...( props as any ) } />;
	}

	if ( type === 'member' ) {
		return <ReplaceMember { ...props } />;
	}

	if ( type === 'keyvalue' ) {
		return <ReplaceKeyValue { ...( props as any ) } />;
	}

	return null;
}

export default ReplaceType;
