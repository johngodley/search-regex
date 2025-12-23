import { useState, FormEvent, ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import SearchForm from '../../page/search-replace/search-form/form';
import { MultiOptionDropdown, createInterpolateElement } from '@wp-plugin-components';
import type { PresetValue, PresetTag } from '../../types/preset';
import type { SearchValues } from '../../types/search';

const MAX_PRESETS = 20;

const emptyTag = (): PresetTag => ( { name: '', title: '' } );

interface PresetEditProps {
	preset: PresetValue;
	onCancel: () => void;
	onUpdate: ( preset: PresetValue ) => void;
}

function PresetEdit( props: PresetEditProps ) {
	const { preset, onCancel, onUpdate } = props;
	const { id, name, search, locked, tags, description } = preset;
	const [ presetValues, setPresetValues ] = useState< SearchValues >( search );
	const [ presetName, setPresetName ] = useState( name );
	const [ presetDescription, setDescription ] = useState( description || '' );
	const [ lockedFields, setLockedFields ] = useState( locked );
	const [ presetTags, setTags ] = useState< PresetTag[] >( tags.length === 0 ? [ emptyTag() ] : tags );

	const availableLockedFields = [
		{
			value: 'searchPhrase',
			label: __( 'Global Search', 'search-regex' ),
		},
		{
			value: 'replacement',
			label: __( 'Global Replace', 'search-regex' ),
		},
		{
			value: 'searchFlags',
			label: __( 'Global Search Flags', 'search-regex' ),
		},
		{
			value: 'source',
			label: __( 'Source', 'search-regex' ),
		},
		{
			value: 'perPage',
			label: __( 'Results per page', 'search-regex' ),
		},
		{
			value: 'filters',
			label: __( 'Filters', 'search-regex' ),
		},
		{
			value: 'action',
			label: __( 'Action', 'search-regex' ),
		},
		{
			value: 'view',
			label: __( 'View Columns', 'search-regex' ),
		},
	];

	function changeTag( pos: number, value: Partial< PresetTag > ) {
		const existingTag = presetTags[ pos ];
		if ( ! existingTag ) {
			return;
		}

		setTags( [
			...presetTags.slice( 0, pos ),
			{
				name: value.name ?? existingTag.name,
				title: value.title ?? existingTag.title,
			},
			...presetTags.slice( pos + 1 ),
		] );
	}

	function addTag() {
		setTags( presetTags.concat( emptyTag() ) );
	}

	function deleteTag( pos: number ) {
		setTags( [ ...presetTags.slice( 0, pos ), ...presetTags.slice( pos + 1 ) ] );
	}

	function cleanTags( tagList: PresetTag[] ): PresetTag[] {
		if ( tagList.length === 1 ) {
			const firstTag = tagList[ 0 ];
			if ( firstTag && firstTag.name === '' && firstTag.title === '' ) {
				return [];
			}
		}

		return tagList;
	}

	function update( ev: FormEvent< HTMLInputElement > ) {
		ev.preventDefault();
		onUpdate( {
			id,
			name: presetName,
			description: presetDescription,
			search: presetValues,
			tags: cleanTags( presetTags ),
			locked: lockedFields,
		} );
	}

	return (
		<td colSpan={ 3 }>
			<table>
				<tbody>
					<tr>
						<td colSpan={ 2 }>
							<h2>{ __( 'Edit preset', 'search-regex' ) } </h2>
						</td>
					</tr>
					<tr className="searchregex-search__search">
						<th>{ __( 'Preset Name', 'search-regex' ) }</th>
						<td>
							<input
								type="text"
								value={ presetName }
								onChange={ ( ev: ChangeEvent< HTMLInputElement > ) => setPresetName( ev.target.value ) }
								placeholder={ __( 'Give the preset a name', 'search-regex' ) }
							/>
						</td>
					</tr>
					<tr className="searchregex-search__search">
						<th>{ __( 'Preset Description', 'search-regex' ) }</th>
						<td>
							<input
								type="text"
								value={ presetDescription }
								onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
									setDescription( ev.target.value )
								}
								placeholder={ __( 'Describe the preset', 'search-regex' ) }
							/>
						</td>
					</tr>

					<SearchForm
						search={ presetValues }
						onSetSearch={ ( values ) => setPresetValues( { ...presetValues, ...values } ) }
						isBusy={ false }
						preset={ null }
					/>

					<tr className="searchregex-search__advanced__title">
						<td colSpan={ 2 }>
							<h2>{ __( 'Advanced preset', 'search-regex' ) } </h2>
						</td>
					</tr>
					<tr className="searchregex-search__advanced">
						<th>{ __( 'Locked Fields', 'search-regex' ) }</th>
						<td>
							<MultiOptionDropdown
								options={ availableLockedFields }
								selected={ lockedFields }
								onChange={ ( fields: string[] | Record< string, string | boolean | undefined > ) => {
									const fieldsArray = Array.isArray( fields ) ? fields : [];
									setLockedFields( fieldsArray );
								} }
								multiple
								title={ __( 'Fields', 'search-regex' ) }
								badges
							/>

							<p>
								{ __(
									'Locking a field removes it from the search form and prevents changes.',
									'search-regex'
								) }
							</p>
						</td>
					</tr>
					<tr className="searchregex-search__advanced">
						<th>{ __( 'Tags', 'search-regex' ) }</th>
						<td>
							{ presetTags.map( ( tag, pos ) => (
								<p key={ pos }>
									{ /* eslint-disable jsx-a11y/label-has-associated-control */ }
									<label>
										{ __( 'Title', 'search-regex' ) }{ ' ' }
										<input
											type="text"
											placeholder={ __( 'Enter tag title shown to user', 'search-regex' ) }
											value={ tag.title }
											onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
												changeTag( pos, { title: ev.target.value } )
											}
										/>
									</label>{ ' ' }
									<label>
										{ __( 'Tag', 'search-regex' ) }{ ' ' }
										<input
											type="text"
											placeholder={ __( 'Enter tag which is used in the field', 'search-regex' ) }
											value={ tag.name }
											onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
												changeTag( pos, { name: ev.target.value } )
											}
										/>
									</label>
									{ /* eslint-enable jsx-a11y/label-has-associated-control */ }
									{ pos < MAX_PRESETS && (
										<button
											type="button"
											onClick={ () =>
												pos === presetTags.length - 1 ? addTag() : deleteTag( pos )
											}
										>
											{ pos === presetTags.length - 1 ? '+' : '-' }
										</button>
									) }
								</p>
							) ) }

							<p>
								{ __(
									'A tag creates a custom input field. Insert the tag anywhere in the search, replace, text filter, or text action and when the preset is used it will be replaced with a custom text field with the tag title.',
									'search-regex'
								) }
							</p>
							<p>
								{ createInterpolateElement(
									__(
										'For example, create tag {{code}}URL{{/code}} and title {{code}}Image URL{{/code}}. Your search could be {{code}}<img src="URL">{{/code}}. When the preset is used it will ask the user for the {{code}}Image URL{{/code}} instead of the full search phrase.',
										'search-regex'
									),
									{
										code: <code />,
									}
								) }
							</p>
						</td>
					</tr>
					<tr>
						<th />
						<td>
							<input
								type="submit"
								className="button button-primary"
								value={ __( 'Save', 'search-regex' ) }
								onClick={ ( ev ) => update( ev ) }
							/>

							<button className="button button-secondary" onClick={ onCancel } type="button">
								{ __( 'Cancel', 'search-regex' ) }
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	);
}

export default PresetEdit;
