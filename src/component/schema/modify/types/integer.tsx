import { useSelector, useDispatch } from 'react-redux';
import Operation from '../operation';
import IntegerInput from '../../../integer-input';
import { setLabel } from '../../../../state/search/action';
import { getLabel } from '../../../../state/search/selector';
import { DropdownText } from '@wp-plugin-components';
import type { SchemaColumn, ModifyIntegerColumn } from '../../../../types/search';

interface ModifyIntegerProps {
	disabled: boolean;
	item: ModifyIntegerColumn;
	onChange: ( values: Partial< ModifyIntegerColumn >, label?: string ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
	fixOperation?: string;
}

export default function ModifyInteger( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
	fixOperation,
}: ModifyIntegerProps ): JSX.Element {
	const { operation = fixOperation || 'set', value = '' } = item;
	const remote = schema.options === 'api' ? fetchData : false;
	const { labels } = useSelector( ( state: { search: { labels: unknown } } ) => state.search );
	const dispatch = useDispatch();

	if ( remote ) {
		return (
			<DropdownText
				value={ value === '' || value === undefined ? [] : [ value ] }
				disabled={ disabled }
				onChange={ ( newValue: string | string[], newLabel?: string | string[] ) =>
					onChange(
						newValue ? { value: ( newValue as string[] )[ 0 ] } : { value: '' },
						newValue ? ( newLabel as string[] )[ 0 ] : ''
					)
				}
				fetchData={ remote }
				loadOnFocus
				maxChoices={ 1 }
				onlyChoices
				setLabel={ ( labelId: string, labelValue: string | null ) =>
					dispatch( setLabel( schema.column + '_' + labelId, labelValue || '' ) )
				}
				getLabel={ ( labelId: string ) =>
					getLabel( labels as { value: string; label: string }[], schema.column + '_' + labelId )
				}
			/>
		);
	}

	return (
		<>
			{ ! fixOperation && (
				<Operation
					type="integer"
					value={ operation }
					disabled={ disabled }
					onChange={ ( newOperation ) =>
						onChange( { operation: newOperation as 'set' | 'increment' | 'decrement', value: '' } )
					}
				/>
			) }

			<IntegerInput
				name="value"
				column={ item.column }
				value={ value }
				onChange={ onChange }
				disabled={ disabled }
				remote={ undefined }
			/>
		</>
	);
}
