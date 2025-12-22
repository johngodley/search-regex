import { type ChangeEvent } from 'react';
import Operation from '../operation';
import { DropdownText, Select } from '@wp-plugin-components';
import { getLabel } from '../../../../lib/search-utils';
import { useSearchStore } from '../../../../stores/search-store';
import type { SchemaColumn, ModifyMemberColumn } from '../../../../types/search';

interface ModifyMemberProps {
	disabled: boolean;
	item: ModifyMemberColumn;
	onChange: ( values: Partial< ModifyMemberColumn >, labels?: string | string[] ) => void;
	schema: SchemaColumn;
	fetchData: ( value: string ) => Promise< unknown >;
	fixOperation?: string;
	localLabels?: Array< { value: string; label: string } >;
}

export default function ModifyMember( {
	disabled,
	item,
	onChange,
	schema,
	fetchData,
	fixOperation,
	localLabels = [],
}: ModifyMemberProps ): JSX.Element {
	const { operation = 'include', values = [] } = item;
	const remote = schema.options === 'api' ? fetchData : false;

	const labels = useSearchStore( ( state ) => state.labels );
	const setLabels = useSearchStore( ( state ) => state.setLabels );
	const isJoin = schema.join !== undefined;

	if ( ! remote ) {
		const options = Array.isArray( schema.options ) ? schema.options : [];
		return (
			<Select
				name="member"
				items={ options }
				value={ ( values[ 0 ] ?? ( options[ 0 ]?.value as string ) ) || '' }
				disabled={ disabled }
				onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => onChange( { values: [ ev.target.value ] } ) }
			/>
		);
	}

	return (
		<>
			{ isJoin && ! fixOperation && (
				<Operation
					type="member"
					value={ operation }
					disabled={ disabled }
					onChange={ ( newOperation ) =>
						onChange( { operation: newOperation as 'set' | 'include' | 'exclude' } )
					}
				/>
			) }

			<DropdownText
				value={ values }
				disabled={ disabled }
				onChange={ ( newValue: string | string[], newLabel?: string | string[] ) => {
					const valueArray = Array.isArray( newValue ) ? newValue : [ newValue ];
					onChange( { values: valueArray }, newLabel );
				} }
				fetchData={ remote }
				{ ...( schema.preload !== undefined && { loadOnFocus: schema.preload } ) }
				maxChoices={ isJoin ? 20 : 1 }
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
					getLabel(
						Array.isArray( labels )
							? [ ...( labels as { value: string; label: string }[] ), ...localLabels ]
							: localLabels,
						schema.column + '_' + labelId
					)
				}
			/>
		</>
	);
}
