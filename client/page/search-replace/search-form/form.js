/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import Replace from 'component/replace';
import { Select, MultiOptionDropdown } from 'wp-plugin-components';
import { getAvailableSearchFlags, getAvailablePerPage } from 'state/search/selector';
import { isLocked, hasTags, getHeaderClass } from 'state/preset/selector';
import { setTagValue } from 'state/search/action';
import { convertToSource, getSourcesForDropdown, customBadge, getSourceFlagOptions } from './utils';
import TaggedPhrases from './tagged-phrase';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('state/preset/type').PresetValue} PresetValue */
/** @typedef {import('./tagged-phrase').ChangeCallback} ChangeCallback */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */

/**
 * @callback SetSearch
 * @param {SearchValues} searchValue
 */

/**
 * Search form
 *
 * @param {object} props - Component props
 * @param {boolean} props.isBusy - Is this form busy?
 * @param {SetSearch} props.onSetSearch -
 * @param {SearchSourceGroup[]} [props.sources] - All sources
 * @param {Object.<string,string>} props.sourceFlagOptions - Array of all the source options
 * @param {SearchValues} props.search - Search values
 * @param {PresetValue|null} [props.preset] - Preset
 * @param {ChangeCallback} [props.onSetTag] - Set a tag value
 * @param {PresetTag[]} [props.tagValues] - Tag values
 */
function Form( { search, onSetSearch, isBusy, sources, sourceFlagOptions, preset, onSetTag, tagValues, tagged } ) {
	const { searchPhrase, searchFlags, sourceFlags, source, perPage, replacement } = search;
	const sourceFlagsForSource = getSourceFlagOptions( sourceFlagOptions, source );
	const locked = preset ? preset.locked : [];
	const tags = preset ? preset.tags : [];
	const headerClass = getHeaderClass( tags );

	/**
	 * @param {Event} ev - Event
	 */
	const setSearchValue = ( ev ) => {
		if ( ev?.target ) {
			onSetSearch( { [ ev.target.name ]: ev.target.value } );
		}
	};

	return (
		<>
			{ preset?.description && (
				<tr>
					<th />
					<td>
						<h3 className="searchregex-preset__description">{ preset.description }</h3>
					</td>
				</tr>
			) }

			{ ( ! isLocked( locked, 'searchFlags' ) || ! isLocked( locked, 'searchPhrase' ) ) &&
				! hasTags( tags, searchPhrase ) && (
					<tr className={ classnames( 'searchregex-search__search', headerClass ) }>
						<th>{ __( 'Search' ) }</th>

						<td>
							{ ! isLocked( locked, 'searchPhrase' ) && (
								<input
									type="text"
									value={ searchPhrase }
									name="searchPhrase"
									placeholder={ __( 'Enter search phrase' ) }
									onChange={ setSearchValue }
									disabled={ isBusy }
								/>
							) }

							{ ! isLocked( locked, 'searchFlags' ) && (
								<MultiOptionDropdown
									options={ getAvailableSearchFlags() }
									selected={ searchFlags }
									onApply={ ( searchFlags ) => onSetSearch( { searchFlags } ) }
									title={ __( 'Search Flags' ) }
									disabled={ isBusy }
									multiple
									badges
								/>
							) }
						</td>
					</tr>
				) }

			{ ( ! isLocked( locked, 'searchFlags' ) || ! isLocked( locked, 'searchPhrase' ) ) &&
				hasTags( tags, searchPhrase ) && (
					<TaggedPhrases
						phrase={ searchPhrase }
						tags={ tags }
						prefix="search"
						onChange={ onSetTag }
						tagValues={ tagValues }
						className={ classnames( headerClass ) }
						preset={ preset }
					/>
				) }

			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, replacement ) && (
				<tr className={ classnames( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace' ) }</th>
					<td>
						<Replace
							canReplace={ ! isBusy }
							setReplace={ ( replacement ) => onSetSearch( { replacement } ) }
							replace={ replacement }
							placeholder={ __( 'Enter global replacement text' ) }
						/>
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'replacement' ) && hasTags( tags, replacement ) && (
				<TaggedPhrases
					phrase={ replacement }
					tags={ tags }
					prefix="replace"
					onChange={ onSetTag }
					tagValues={ tagValues }
					className={ classnames( headerClass ) }
					preset={ preset }
				/>
			) }

			{ ( ! isLocked( locked, 'source' ) || ! isLocked( locked, 'sourceFlags' ) ) && (
				<tr className={ classnames( 'searchregex-search__source', headerClass ) }>
					<th>{ __( 'Source' ) }</th>
					<td>
						{ ! isLocked( locked, 'source' ) && (
							<MultiOptionDropdown
								options={ getSourcesForDropdown( sources ) }
								selected={ source }
								onApply={ ( selected, optionValue ) =>
									onSetSearch( { source: convertToSource( selected, optionValue, sources ) } )
								}
								multiple
								disabled={ isBusy }
								badges
								customBadge={ ( badges ) => customBadge( badges, sources ) }
							/>
						) }

						{ ! isLocked( locked, 'sourceFlags' ) && Object.keys( sourceFlagsForSource ).length > 0 && (
							<MultiOptionDropdown
								options={ sourceFlagsForSource }
								selected={ sourceFlags }
								onApply={ ( sourceFlags ) => onSetSearch( { sourceFlags } ) }
								title={ __( 'Source Options' ) }
								disabled={ isBusy }
								badges
								hideTitle
							/>
						) }
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'perPage' ) && (
				<tr className={ classnames( headerClass ) }>
					<th>{ __( 'Results' ) }</th>
					<td>
						<Select
							name="perPage"
							items={ getAvailablePerPage() }
							value={ perPage }
							onChange={ ( ev ) => onSetSearch( { perPage: parseInt( ev.target.value, 10 ) } ) }
							disabled={ isBusy }
						/>
					</td>
				</tr>
			) }

			{ tagged.searchPhrase || tagged.replacement ? (
				<tr className={ classnames( headerClass ) }>
					<th />
					<td>
						<code>{ tagged.searchPhrase ? tagged.searchPhrase : <em>{ __( 'Empty' ) }</em> }</code>
						{ tagged.replacement && (
							<>
								&nbsp;→&nbsp;
								<code>{ tagged.replacement }</code>
							</>
						) }
					</td>
				</tr>
			) : null }
		</>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		/**
		 * @param {string} prefix - Tag prefix
		 * @param {string} tagName - Tag name
		 * @param {number} position - Tag position
		 * @param {string} tagValue - Tag value
		 */
		onSetTag: ( prefix, tagName, position, tagValue, preset ) => {
			dispatch( setTagValue( prefix, tagName, position, tagValue, preset ) );
		},
	};
}

function mapStateToProps( state ) {
	const { sources, sourceFlags, tagged, tagValues } = state.search;

	return {
		sources,
		sourceFlagOptions: sourceFlags,
		tagValues,
		tagged,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Form );
