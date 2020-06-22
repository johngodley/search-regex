/**
 * External dependencies
 */

import React, { useState } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */

import SearchForm from 'page/search-replace/search-form/form';
import { MultiOptionDropdown } from 'wp-plugin-components';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/preset/type.js').PresetValue} PresetValue */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */

/** @type {number} */
const MAX_PRESETS = 20;

/**
 * @callback CancelCallback
 */

/**
 * @callback UpdateCallback
 * @param {PresetValue} preset - Preset
 */

/**
 * @returns {PresetTag}
 */
const emptyTag = () => ( { name: '', title: '' } );

/**
 * Edit a preset
 *
 * @param {object} props - Component props
 * @param {PresetValue} props.preset - Preset
 * @param {CancelCallback} props.onCancel - Callback to cancel editing the preset
 * @param {UpdateCallback} props.onUpdate - Callback to update the preset
 */
function PresetEdit( props ) {
	const { preset, onCancel, onUpdate } = props;
	const { name, search, locked, tags, description } = preset;
	const [ presetValues, setPresetValues ] = useState( search );
	const [ presetName, setPresetName ] = useState( name );
	const [ presetDescription, setDescription ] = useState( description );
	const [ lockedFields, setLockedFields ] = useState( locked );
	const [ presetTags, setTags ] = useState( tags.length === 0 ? [ emptyTag() ] : tags );

	const availableLockedFields = [
		{
			value: 'searchPhrase',
			label: __( 'Search' ),
		},
		{
			value: 'replacement',
			label: __( 'Replace' ),
		},
		{
			value: 'searchFlags',
			label: __( 'Search Flags' ),
		},
		{
			value: 'source',
			label: __( 'Source' ),
		},
		{
			value: 'sourceFlags',
			label: __( 'Source Flags' ),
		},
		{
			value: 'perPage',
			label: __( 'Results per page' ),
		},
	];

	/**
	 * Update a tag
	 * @param {number} pos - Tag position
	 * @param {object} value - Tag
	 */
	function changeTag( pos, value ) {
		setTags( [
			...presetTags.slice( 0, pos ),
			{
				...presetTags[ pos ],
				...value,
			},
			...presetTags.slice( pos + 1 ),
		] );
	}

	function addTag() {
		setTags( presetTags.concat( emptyTag() ) );
	}

	/**
	 * Delete a tag
	 * @param {number} pos - Tag position
	 */
	function deleteTag( pos ) {
		setTags( [ ...presetTags.slice( 0, pos ), ...presetTags.slice( pos + 1 ) ] );
	}

	function update( ev ) {
		ev.preventDefault();
		onUpdate( { name: presetName, description: presetDescription, search: presetValues, tags: presetTags, locked: lockedFields } );
	}

	return (
		<td colSpan={ 3 }>
			<table>
				<tbody>
					<tr>
						<td colSpan={ 2 }>
							<h2>{ __( 'Edit preset' ) } </h2>
						</td>
					</tr>
					<tr className="searchregex-search__search">
						<th>{ __( 'Preset Name' ) }</th>
						<td>
							<input
								type="text"
								value={ presetName }
								onChange={ ( ev ) => setPresetName( ev.target.value ) }
								placeholder={ __( 'Give the preset a name' ) }
							/>
						</td>
					</tr>
					<tr className="searchregex-search__search">
						<th>{ __( 'Preset Description' ) }</th>
						<td>
							<input
								type="text"
								value={ presetDescription }
								onChange={ ( ev ) => setDescription( ev.target.value ) }
								placeholder={ __( 'Describe the preset' ) }
							/>
						</td>
					</tr>

					<SearchForm
						search={ presetValues }
						onSetSearch={ ( values ) => setPresetValues( { ...presetValues, ...values } ) }
						isBusy={ false }
					/>

					<tr className="searchregex-search__advanced__title">
						<td colSpan={ 2 }>
							<h2>{ __( 'Advanced preset' ) } </h2>
						</td>
					</tr>
					<tr className="searchregex-search__advanced">
						<th>{ __( 'Locked Fields' ) }</th>
						<td>
							<MultiOptionDropdown
								options={ availableLockedFields }
								selected={ lockedFields }
								onApply={ ( fields ) => setLockedFields( fields ) }
								multiple
								title={ __( 'Fields' ) }
								badges
							/>

							<p>{ __( 'Locking a field removes it from the search form and prevents changes.' ) }</p>
						</td>
					</tr>
					<tr className="searchregex-search__advanced">
						<th>{ __( 'Tags' ) }</th>
						<td>
							{ presetTags.map( ( tag, pos ) => (
								<p key={ pos }>
									<label>
										{ __( 'Title' ) }
										<input
											type="text"
											placeholder={ __( 'Enter tag title shown to user' ) }
											value={ tag.title }
											onChange={ ( ev ) => changeTag( pos, { title: ev.target.value } ) }
										/>
									</label>

									<label>
										{ __( 'Tag' ) }
										<input
											type="text"
											placeholder={ __( 'Enter tag which is used in the field' ) }
											value={ tag.name }
											onChange={ ( ev ) => changeTag( pos, { name: ev.target.value } ) }
										/>
									</label>

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
									'A tag creates a custom input field. Insert the tag anywhere in the search or replace field and when the preset is used it will be replaced with a custom text field with the tag label.'
								) }
							</p>
							<p>
								{ __(
									'For example, create tag {{code}}URL{{/code}} and title {{code}}Image URL{{/code}}. Your search could be {{code}}<img src="URL">{{/code}}. When the preset is used it will ask the user for the {{code}}Image URL{{/code}} instead of the full search phrase.',
									{
										components: {
											code: <code />,
										},
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
								value={ __( 'Save' ) }
								onClick={ ( ev ) => update( ev ) }
							/>

							<button className="button button-secondary" onClick={ onCancel } type="button">
								{ __( 'Cancel' ) }
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	);
}

export default PresetEdit;
