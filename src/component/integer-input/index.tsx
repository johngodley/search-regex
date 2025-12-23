import { getLabel } from '../../lib/search-utils';
import { DropdownText } from '@wp-plugin-components';
import { useSearchStore } from '../../stores/search-store';
import './style.scss';

interface IntegerInputProps {
	value: string | number;
	name: string;
	onChange: ( value: Record< string, string > ) => void;
	disabled?: boolean;
	remote?: string;
	column: string;
}

function IntegerInput( { value, name, onChange, disabled, remote, column }: IntegerInputProps ): JSX.Element {
	const labels = useSearchStore( ( state ) => state.labels );
	const setLabels = useSearchStore( ( state ) => state.setLabels );
	const stringValue = value === undefined || value === null ? '' : String( value );

	function sanitize( newValue: string ): string {
		const numberOnly = String( newValue )
			.replace( /[^0-9]/g, '' )
			.trim();

		if ( numberOnly.length > 0 ) {
			return String( parseInt( numberOnly, 10 ) );
		}

		return '';
	}

	const dropdownProps = {
		name,
		className: 'searchregex-integer-input',
		value: stringValue,
		onBlur: sanitize,
		onChange: ( newValue: string | string[] ) => {
			const changedValue = Array.isArray( newValue ) ? newValue[ 0 ] : newValue;
			if ( changedValue !== undefined ) {
				onChange( { [ name ]: changedValue } );
			}
		},
		canMakeRequest: ( checkValue: string ) =>
			checkValue.length > 0 && checkValue.replace( /[0-9]/g, '' ).length > 0,
		maxChoices: remote ? 1 : -1,
		onlyChoices: remote ? true : false,
		setLabel: ( labelId: string, labelValue: string | null ) => {
			// Update labels array with new label
			const existingIndex = ( labels as Array< { value: string; label: string } > ).findIndex(
				( l ) => l.value === column + '_' + labelId
			);

			if ( existingIndex >= 0 ) {
				const updatedLabels = [ ...( labels as Array< { value: string; label: string } > ) ];
				updatedLabels[ existingIndex ] = { value: column + '_' + labelId, label: labelValue || '' };
				setLabels( updatedLabels );
			} else {
				setLabels( [
					...( labels as Array< { value: string; label: string } > ),
					{ value: column + '_' + labelId, label: labelValue || '' },
				] );
			}
		},
		getLabel: ( labelId: string, labelValues: string | string[] ) => {
			const labelValue = Array.isArray( labelValues ) ? labelValues[ 0 ] : labelValues;
			return getLabel( labels as Array< { value: string; label: string } >, column + '_' + labelId, labelValue );
		},
		...( disabled !== undefined ? { disabled } : {} ),
		...( remote
			? {
					fetchData: ( searchTerm: string ) =>
						fetch( remote + '?search=' + encodeURIComponent( searchTerm ) ).then( ( res ) => res.json() ),
			  }
			: {} ),
	};

	return <DropdownText { ...dropdownProps } />;
}

export default IntegerInput;
