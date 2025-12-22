import Operation from '../operation';
import IntegerInput from '../../../integer-input';
import { getLabel } from '../../../../lib/search-utils';
import { DropdownText } from '@wp-plugin-components';
import { useSearchStore } from '../../../../stores/search-store';
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

	const labels = useSearchStore( ( state ) => state.labels );
	const setLabels = useSearchStore( ( state ) => state.setLabels );

	if ( remote ) {
		return (
			<DropdownText
				value={ value === '' || value === undefined ? [] : [ value ] }
				disabled={ disabled }
				onChange={ ( newValue: string | string[], newLabel?: string | string[] ) => {
					const firstValue = ( newValue as string[] )[ 0 ];
					const firstLabel = ( newLabel as string[] )[ 0 ];
					if ( newValue && firstValue ) {
						onChange( { value: firstValue }, firstLabel ?? '' );
					} else {
						onChange( { value: '' }, '' );
					}
				} }
				fetchData={ remote }
				loadOnFocus
				maxChoices={ 1 }
				onlyChoices
				setLabel={ ( labelId: string, labelValue: string | null ) => {
					const existingIndex = ( labels as Array< { value: string; label: string } > ).findIndex(
						( l ) => l.value === schema.column + '_' + labelId
					);

					if ( existingIndex >= 0 ) {
						const updatedLabels = [ ...( labels as Array< { value: string; label: string } > ) ];
						updatedLabels[ existingIndex ] = {
							value: schema.column + '_' + labelId,
							label: labelValue || '',
						};
						setLabels( updatedLabels );
					} else {
						setLabels( [
							...( labels as Array< { value: string; label: string } > ),
							{ value: schema.column + '_' + labelId, label: labelValue || '' },
						] );
					}
				} }
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
			/>
		</>
	);
}
