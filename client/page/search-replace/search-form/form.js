/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { connect } from 'react-redux';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import Replace from 'component/replace';
import { Select, MultiOptionDropdown } from 'wp-plugin-components';
import { getAvailableSearchFlags, getAvailablePerPage } from 'state/search/selector';
import { isLocked, hasTags, getHeaderClass, getDefaultPresetValues } from 'state/preset/selector';
import Search from 'component/search';
import { convertToSource, getSourcesForDropdown, customBadge, getSourceFlagOptions } from './utils';

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('state/preset/type').PresetValue} PresetValue */
/** @typedef {import('component/tagged-phrase').ChangeCallback} ChangeCallback */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */
/** @typedef {import('react').SyntheticEvent} SyntheticEvent */

/**
 * @callback SetSearch
 * @param {SearchValues} searchValue
 */

function showPresetValue( search, replace, defaults ) {
	if ( defaults ) {
		if ( search !== defaults.searchPhrase && search !== '' ) {
			return true;
		}

		if ( replace !== '' && replace !== defaults.replacement ) {
			return true;
		}
	}

	return false;
}

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
 */
function Form( { search, onSetSearch, isBusy, sources, sourceFlagOptions, preset } ) {
	const { searchPhrase, searchFlags, sourceFlags, source, perPage, replacement } = search;
	const sourceFlagsForSource = getSourceFlagOptions( sourceFlagOptions, source );
	const locked = preset ? preset.locked : [];
	const tags = preset ? preset.tags : [];
	const headerClass = getHeaderClass( tags );
	const defaultValues = getDefaultPresetValues( preset );

	function setTaggedReplace( replacement ) {
		const defaults = getDefaultPresetValues( preset );

		// If the replace is the default non-tagged replace then reset it to an empty string
		onSetSearch( { replacement: replacement === defaults.replacement ? '' : replacement } );
	}

	return (
		<>
			{ preset && preset.description ? (
				<tr>
					<th />
					<td>
						<h3 className="searchregex-preset__description">{ preset.description }</h3>
					</td>
				</tr>
			) : null }

			{ ( ! isLocked( locked, 'searchFlags' ) || ! isLocked( locked, 'searchPhrase' ) ) &&
				! hasTags( tags, preset?.search?.searchPhrase ?? '' ) && (
					<tr className={ classnames( 'searchregex-search__search', headerClass ) }>
						<th>{ __( 'Search' ) }</th>

						<td>
							{ ! isLocked( locked, 'searchPhrase' ) && (
								<Search
									disabled={ isBusy }
									value={ searchPhrase }
									onChange={ ( value ) => onSetSearch( { searchPhrase: value } ) }
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
				hasTags( tags, preset?.search?.searchPhrase ?? '' ) && (
					<Search
						disabled={ isBusy }
						value={ searchPhrase }
						preset={ preset }
						onChange={ ( value ) => onSetSearch( { searchPhrase: value } ) }
						className={ headerClass }
					/>
				) }

			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__replace', headerClass ) }>
					<th>{ __( 'Replace' ) }</th>
					<td>
						<Replace
							disabled={ isBusy }
							setReplace={ ( replacement ) => onSetSearch( { replacement } ) }
							replace={ replacement }
							placeholder={ __( 'Enter global replacement text' ) }
						/>
					</td>
				</tr>
			) }

			{ ! isLocked( locked, 'replacement' ) && hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<Replace
					preset={ preset }
					disabled={ isBusy }
					setReplace={ setTaggedReplace }
					replace={ replacement }
					placeholder={ __( 'Enter global replacement text' ) }
					className={ headerClass }
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

			{ showPresetValue( searchPhrase, replacement, defaultValues ) ? (
				<tr className={ classnames( headerClass ) }>
					<th />
					<td>
						<code>{ searchPhrase }</code>
						{ replacement && (
							<>
								&nbsp;→&nbsp; <code>{ replacement }</code>
							</>
						) }
					</td>
				</tr>
			) : null }
		</>
	);
}

function mapStateToProps( state ) {
	const { sources, sourceFlags } = state.search;

	return {
		sources,
		sourceFlagOptions: sourceFlags,
	};
}

export default connect(
	mapStateToProps,
	null
)( Form );
