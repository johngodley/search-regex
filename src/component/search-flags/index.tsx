import { __ } from '@wordpress/i18n';
import { MultiOptionDropdown } from '@wp-plugin-components';
import { getAvailableSearchFlags } from '../../lib/search-utils';

interface SearchFlagsProps {
	flags: string[];
	onChange: ( flags: string[] ) => void;
	disabled?: boolean;
	allowRegex?: boolean;
	allowMultiline?: boolean;
	allowCase?: boolean;
}

/**
 * Search flags component
 * @param props
 */
function SearchFlags( props: SearchFlagsProps ): JSX.Element {
	const { flags, onChange, disabled, allowRegex = true, allowMultiline = false, allowCase = true } = props;
	const options = getAvailableSearchFlags()
		.filter( ( item ) => item.value !== 'regex' || allowRegex )
		.filter( ( item ) => item.value !== 'case' || allowCase )
		.filter( ( item ) => item.value !== 'multi' || allowMultiline );

	return (
		<MultiOptionDropdown
			options={ options }
			selected={ flags }
			onChange={ ( searchFlags: string[] | Record< string, string | boolean | undefined > ) => {
				const flagsArray = Array.isArray( searchFlags ) ? searchFlags : [];
				onChange( flagsArray );
			} }
			title={ __( 'Flags', 'search-regex' ) }
			{ ...( disabled !== undefined ? { disabled } : {} ) }
			multiple
			badges
		/>
	);
}

export default SearchFlags;
