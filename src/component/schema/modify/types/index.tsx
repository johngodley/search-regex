import ModifyInteger from './integer';
import ModifyDate from './date';
import ModifyString from './string';
import ModifyMember from './member';
import ModifyKeyValue from './keyvalue';
import type { SchemaColumn, ModifyColumn } from '../../../../types/search';

interface ModifyTypeProps {
	schema: SchemaColumn;
	item: ModifyColumn;
	disabled: boolean;
	fetchData: ( value: string ) => Promise< unknown >;
	onChange: ( values: Partial< ModifyColumn > ) => void;
	fixOperation?: string;
	localLabels?: Array< { value: string; label: string } >;
}

function ModifyType( props: ModifyTypeProps ): JSX.Element | null {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <ModifyInteger { ...( props as any ) } />;
	}

	if ( type === 'date' ) {
		return <ModifyDate { ...( props as any ) } />;
	}

	if ( type === 'string' ) {
		return <ModifyString { ...( props as any ) } />;
	}

	if ( type === 'member' ) {
		return <ModifyMember { ...( props as any ) } />;
	}

	if ( type === 'keyvalue' ) {
		return <ModifyKeyValue { ...( props as any ) } />;
	}

	return null;
}

export default ModifyType;
