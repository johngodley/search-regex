import { useSelector, useDispatch } from 'react-redux';
import { setLabel } from '../../state/search/action';
import { getLabel } from '../../state/search/selector';
import { DropdownText } from '@wp-plugin-components';
import './style.scss';

interface IntegerInputProps {
	value: string | number;
	name: string;
	onChange: ( value: Record< string, string > ) => void;
	disabled?: boolean;
	remote?: string;
	column: string;
}

interface SearchState {
	labels: Array< { value: string; label: string; labelId?: string } >;
}

interface RootState {
	search: SearchState;
}

function IntegerInput( { value, name, onChange, disabled, remote, column }: IntegerInputProps ): JSX.Element {
	const { labels } = useSelector( ( state: RootState ) => state.search );
	const dispatch = useDispatch();
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

	return (
		<DropdownText
			name={ name }
			className="searchregex-integer-input"
			value={ stringValue }
			disabled={ disabled }
			onBlur={ sanitize }
			onChange={ ( newValue: string | string[] ) => {
				const changedValue = Array.isArray( newValue ) ? newValue[ 0 ] : newValue;
				onChange( { [ name ]: changedValue } );
			} }
			fetchData={
				remote
					? ( searchTerm: string ) => fetch( remote + '?search=' + searchTerm ).then( ( res ) => res.json() )
					: undefined
			}
			canMakeRequest={ ( checkValue: string ) =>
				checkValue.length > 0 && checkValue.replace( /[0-9]/g, '' ).length > 0
			}
			maxChoices={ remote ? 1 : -1 }
			onlyChoices={ remote ? true : false }
			setLabel={ ( labelId: string, labelValue: string | null ) =>
				dispatch( setLabel( column + '_' + labelId, labelValue || '' ) )
			}
			getLabel={ ( labelId: string, labelValues: string | string[] ) => {
				const labelValue = Array.isArray( labelValues ) ? labelValues[ 0 ] : labelValues;
				return getLabel( labels, column + '_' + labelId, labelValue );
			} }
		/>
	);
}

export default IntegerInput;
