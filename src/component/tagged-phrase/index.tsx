import { useState } from 'react';
import classnames from 'classnames';
import replaceSearchTags from './tag';
import type { PresetTag } from '../../types/preset';

interface TaggedPhrasesProps {
	search: {
		searchPhrase: string;
		replacement: string;
		filters: Array< { items: Array< { value?: string; [ key: string ]: unknown } >; [ key: string ]: unknown } >;
		actionOption: Array< { searchValue?: string; replaceValue?: string; [ key: string ]: unknown } > | unknown;
	};
	values: Record< string, unknown >;
	onChange: ( values: Record< string, unknown > ) => void;
	className?: string;
	tags: PresetTag[];
	disabled?: boolean;
}

/**
 * Tagged phrases component
 * @param props
 */
function TaggedPhrases( props: TaggedPhrasesProps ): JSX.Element[] {
	const { search, values, onChange, className, tags, disabled = false } = props;
	const [ tagValues, setTagValues ] = useState< string[] >( tags.map( () => '' ) );

	function updateTag( value: string, pos: number ): void {
		const newValues = tagValues.slice( 0, pos ).concat( value, tagValues.slice( pos + 1 ) );

		setTagValues( newValues );
		onChange( { ...values, ...replaceSearchTags( search, tags, newValues ) } );
	}

	return tags.map( ( tag, pos ) => (
		<tr className={ classnames( 'searchregex-preset__tag', className ) } key={ tag.name }>
			<th>{ tag.label }</th>
			<td>
				<input
					type="text"
					value={ tagValues[ pos ] }
					placeholder={ tag.label }
					onChange={ ( ev ) => updateTag( ev.target.value, pos ) }
					disabled={ disabled }
				/>
			</td>
		</tr>
	) );
}

export default TaggedPhrases;
