import FilterInteger from './integer';
import FilterDate from './date';
import FilterString from './string';
import FilterMember from './member';
import FilterKeyValue from './keyvalue';
import type { SchemaColumn, FilterItem } from '../../../../types/search';

interface FilterTypeProps {
	schema: SchemaColumn;
	item: FilterItem;
	disabled: boolean;
	fetchData: ( value: string ) => Promise< unknown >;
	onChange: ( values: Partial< FilterItem > ) => void;
}

function FilterType( props: FilterTypeProps ): JSX.Element | null {
	const { type } = props.schema;

	if ( type === 'integer' ) {
		return <FilterInteger { ...props } />;
	}

	if ( type === 'date' ) {
		return <FilterDate { ...props } />;
	}

	if ( type === 'string' ) {
		return <FilterString { ...props } />;
	}

	if ( type === 'member' ) {
		return <FilterMember { ...props } />;
	}

	if ( type === 'keyvalue' ) {
		return <FilterKeyValue { ...props } />;
	}

	return null;
}

export default FilterType;
