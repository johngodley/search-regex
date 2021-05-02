/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { translate as __ } from 'i18n-calypso';
import { connect } from 'react-redux';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { Select, Button, MultiOptionDropdown } from 'wp-plugin-components';
import {
	getAvailablePerPage,
	getDefaultFilters,
	getSearchOptionsForSources,
	getSchema,
	getFilterForType,
} from 'state/search/selector';
import { isLocked, hasTags, getHeaderClass, getDefaultPresetValues } from 'state/preset/selector';
import { hasFilterTag, hasActionTag } from 'state/preset/selector';
import Search from 'component/search';
import Filters from './filters';
import { convertToSource, getSourcesForDropdown } from './utils';
import Actions from '../actions';
import SearchFlags from 'component/search-flags';
import TaggedPhrases from 'component/tagged-phrase';

const MAX_AND_FILTERS = 20;

/** @typedef {import('state/search/type.js').SearchValues} SearchValues */
/** @typedef {import('state/search/type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('state/preset/type').PresetValue} PresetValue */
/** @typedef {import('component/tagged-phrase').ChangeCallback} ChangeCallback */
/** @typedef {import('state/preset/type.js').PresetTag} PresetTag */
/** @typedef {import('react').SyntheticEvent} SyntheticEvent */
/** @typedef {import('state/search/type.js').Schema} Schema */
/** @typedef {import('wp-plugin-components/select').SelectOption} SelectOption */

/**
 * @callback SetSearch
 * @param {SearchValues} searchValue
 */

function hasFilter( filters, source, column ) {
	return (
		filters.find(
			( item ) => item.type === source && item.items.find( ( itemFind ) => itemFind.column === column )
		) !== undefined
	);
}

function hasAction( actions, source, column ) {
	if ( ! Array.isArray( actions ) ) {
		return false;
	}

	return actions.find( ( action ) => action.source === source && action.column === column ) !== undefined;
}

function getColumnsForDropdown( sources, schema, filters, actions ) {
	return sources
		.map( ( source ) => {
			const sourceSchema = getSchema( schema, source );
			const columns = sourceSchema.columns.filter(
				( column ) =>
					! hasFilter( filters, source, column.column ) && ! hasAction( actions, source, column.column )
			);

			if ( columns.length > 0 ) {
				return {
					value: source,
					label: sourceSchema.name,
					options: columns.map( ( column ) => {
						return {
							value: source + '__' + column.column,
							label: column.title,
						};
					} ),
				};
			}

			return false;
		} )
		.filter( Boolean );
}

/**
 * Show a preset value
 * @param {string} search
 * @param {string} replace
 * @param {object} defaults
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

// Check the string filters for tags
function hasVisibleFilters( filters, tags ) {
	if ( tags.length === 0 ) {
		return true;
	}

	let tagCount = 0,
		total = 0;

	for ( let index = 0; index < filters.length; index++ ) {
		for ( let itemIndex = 0; itemIndex < filters[ index ].items.length; itemIndex++ ) {
			total++;
			tagCount += hasFilterTag( tags, filters[ index ].items[ itemIndex ] ) ? 1 : 0;
		}
	}

	return tagCount !== total;
}

function hasVisibleAction( actionOption, tags, preset ) {
	if ( ! preset || ! Array.isArray( actionOption ) || tags.length === 0 ) {
		return true;
	}

	let tagCount = 0,
		total = 0;

	for ( let index = 0; index < actionOption.length; index++ ) {
		total++;
		tagCount += hasActionTag( tags, actionOption[ index ] ) ? 1 : 0;
	}

	return tagCount !== total;
}

/**
 * Search form
 *
 * @param {object} props - Component props
 * @param {boolean} props.isBusy - Is this form busy?
 * @param {SetSearch} props.onSetSearch -
 * @param {SearchSourceGroup[]} props.sources - All sources
 * @param {SearchValues} props.search - Search values
 * @param {PresetValue|null} props.preset - Preset
 * @param {Schema[]} props.schema - Schema
 */
function Form( { search, onSetSearch, isBusy, sources, preset, schema } ) {
	const { searchPhrase, searchFlags, source, perPage, replacement, filters = [], actionOption, view = [] } = search;
	const locked = preset ? preset.locked : [];
	const tags = preset ? preset.tags : [];
	const headerClass = getHeaderClass( tags );
	const defaultValues = getDefaultPresetValues( preset );
	const filterOptions = getSearchOptionsForSources( source, schema );
	const [ currentFilter, setCurrentFilter ] = useState( filterOptions.length > 0 ? filterOptions[ 0 ].value : '' );
	const viewFilters = hasVisibleFilters( filters, tags ) && ! isLocked( locked, 'filters' );

	useEffect(() => {
		if ( filterOptions.indexOf( currentFilter ) === -1 ) {
			setCurrentFilter( filterOptions.length > 0 ? filterOptions[ 0 ].value : '' );
		}
	}, [ source ]);

	function applySources( selected, changed ) {
		const newSource = convertToSource( selected );
		const allowedFilters = getSearchOptionsForSources( newSource, schema ).map( ( item ) => item.value );
		const newFilters = newSource.indexOf( changed ) !== -1 ? getDefaultFilters( changed ) : [];

		return {
			source: newSource,
			filters: filters.filter( ( f ) => allowedFilters.indexOf( f.type ) !== -1 ).concat( newFilters ),
			actionOption: [],
			view: view.filter( ( f ) => newSource.indexOf( f.split( '__' )[ 0 ] ) !== -1 ),
		};
	}

	function addFilter() {
		onSetSearch( {
			filters: filters.concat( getFilterForType( currentFilter, getSchema( schema, currentFilter ) ) ),
		} );
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

			{ ( ! isLocked( locked, 'source' ) || viewFilters ) && (
				<tr className={ classnames( 'searchregex-search__source', headerClass ) }>
					<th>{ __( 'Source' ) }</th>
					<td>
						{ ! isLocked( locked, 'source' ) && (
							<MultiOptionDropdown
								options={ getSourcesForDropdown( sources ) }
								selected={ source }
								onApply={ ( selected, optionValue ) =>
									onSetSearch( applySources( selected, optionValue ) )
								}
								multiple
								disabled={ isBusy }
								badges
								aria-label={ __( 'Sources' ) }
							/>
						) }

						{ viewFilters && (
							<>
								<span>
									<strong>{ __( 'Filters' ) }</strong>
								</span>
								<Select
									disabled={ isBusy }
									name="filter"
									value={ currentFilter }
									onChange={ ( ev ) => setCurrentFilter( ev.target.value ) }
									items={ filterOptions }
								/>
								<Button
									onClick={ addFilter }
									disabled={ isBusy || filters.length >= MAX_AND_FILTERS }
								>
									{ __( 'Add' ) }
								</Button>
							</>
						) }
					</td>
				</tr>
			) }

			{ viewFilters && (
				<Filters
					filters={ filters }
					disabled={ isBusy }
					onSetSearch={ onSetSearch }
					tags={ tags }
					presetFilters={ preset?.search?.filters ?? [] }
				/>
			) }

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
									multiline={ searchFlags.indexOf( 'multi' ) !== -1 }
								/>
							) }

							{ ! isLocked( locked, 'searchFlags' ) && (
								<SearchFlags
									flags={ searchFlags }
									disabled={ isBusy }
									onChange={ ( flags ) => onSetSearch( { searchFlags: flags } ) }
									allowMultiline
								/>
							) }
						</td>
					</tr>
				) }

			{ preset && (
				<TaggedPhrases
					disabled={ isBusy }
					search={ preset.search }
					onChange={ ( value ) => onSetSearch( value ) }
					tags={ tags }
					className={ headerClass }
					key={ preset.id }
				/>
			) }

			{ ! isLocked( locked, 'action' ) && hasVisibleAction( tags, actionOption, preset ) && (
				<Actions
					locked={ locked }
					tags={ tags }
					preset={ preset }
					headerClass={ headerClass }
					searchPhrase={ searchPhrase }
					disabled={ isBusy }
					sources={ source }
					onSetSearch={ onSetSearch }
					search={ search }
				/>
			) }
			{ ( ! isLocked( locked, 'perPage' ) || ! isLocked( locked, 'view' ) ) && (
				<tr className={ classnames( 'searchregex-search__results', headerClass ) }>
					<th>{ __( 'Results' ) }</th>
					<td>
						{ ! isLocked( locked, 'perPage' ) && (
							<Select
								name="perPage"
								items={ getAvailablePerPage() }
								value={ perPage }
								onChange={ ( ev ) => onSetSearch( { perPage: parseInt( ev.target.value, 10 ) } ) }
								disabled={ isBusy }
							/>
						) }

						{ ! isLocked( locked, 'view' ) && (
							<MultiOptionDropdown
								options={ getColumnsForDropdown( source, schema, filters, actionOption ) }
								selected={ view }
								onApply={ ( selected ) => onSetSearch( { view: selected } ) }
								multiple
								disabled={ isBusy }
								title={ __( 'View Columns' ) }
							/>
						) }
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
	const { sources, schema } = state.search;

	return {
		sources,
		schema,
	};
}

export default connect(
	mapStateToProps,
	null
)( Form );
