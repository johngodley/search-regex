import { type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import TextareaAutosize from 'react-textarea-autosize';

interface SearchProps {
	disabled?: boolean;
	value: string;
	onChange: ( value: string ) => void;
	className?: string;
	multiline?: boolean;
	placeholder?: string;
	multilinePlaceholder?: string;
}

function Search( props: SearchProps ): JSX.Element {
	const {
		value,
		onChange,
		disabled = false,
		multiline = false,
		className,
		placeholder,
		multilinePlaceholder,
	} = props;

	if ( multiline ) {
		return (
			<TextareaAutosize
				value={ value }
				name="searchPhrase"
				cols={ 120 }
				minRows={ 2 }
				maxRows={ 5 }
				onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) => onChange( ev.target.value ) }
				disabled={ disabled }
				className={ className }
				placeholder={
					multilinePlaceholder ?? __( 'Enter search phrase', 'search-regex' )
				}
			/>
		);
	}

	return (
		<input
			value={ value }
			type="text"
			name="searchPhrase"
			disabled={ disabled }
			onChange={ ( ev: ChangeEvent< HTMLInputElement > ) => onChange( ev.target.value ) }
			className={ className }
			placeholder={
				placeholder ??
				__( 'Optional global search phrase. Leave blank to use filters only.', 'search-regex' )
			}
		/>
	);
}

export default Search;
