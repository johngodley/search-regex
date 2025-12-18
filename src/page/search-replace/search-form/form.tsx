import { useEffect, useState, ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import classnames from 'classnames';
import { Select, Button, MultiOptionDropdown } from '@wp-plugin-components';
import {
	getAvailablePerPage,
	getDefaultFilters,
	getSearchOptionsForSources,
	getSchema,
	getFilterForType,
} from '../../../state/search/selector';
import {
	isLocked,
	hasTags,
	getHeaderClass,
	getDefaultPresetValues,
	hasFilterTag,
	hasActionTag,
} from '../../../state/preset/selector';
import Search from '../../../component/search';
import Filters from './filters';
import { convertToSource, getSourcesForDropdown } from './utils';
import Actions from '../actions';
import SearchFlags from '../../../component/search-flags';
import TaggedPhrases from '../../../component/tagged-phrase';
import type { PresetValue, PresetTag } from '../../../types/preset';
import type {
	Schema,
	Filter,
	FilterItem,
	SearchValues as ImportedSearchValues,
	SearchSourceGroup,
} from '../../../types/search';

const MAX_AND_FILTERS = 20;

// FilterGroup is an alias for Filter - filters.tsx uses this name
type FilterGroup = Filter;

interface ActionOption {
	column?: string;
	source?: string;
	searchValue?: string;
	replaceValue?: string;
	[ key: string ]: unknown;
}

interface SearchValues extends ImportedSearchValues {
	filters?: FilterGroup[];
	actionOption?: ActionOption | ActionOption[];
	view?: string[];
}

interface RootState {
	search: {
		sources: SearchSourceGroup[];
		schema: Schema[];
	};
}

interface FormProps {
	search: SearchValues;
	onSetSearch: ( values: SearchValues ) => void;
	isBusy: boolean;
	sources: SearchSourceGroup[];
	preset: PresetValue | null;
	schema: Schema[];
}

function hasFilter( filters: FilterGroup[], source: string, column: string ): boolean {
	return (
		filters.find(
			( item ) =>
				item.type === source && item.items.find( ( itemFind: FilterItem ) => itemFind.column === column )
		) !== undefined
	);
}

function hasAction( actions: ActionOption | ActionOption[], source: string, column: string ): boolean {
	if ( ! Array.isArray( actions ) ) {
		return false;
	}

	return actions.find( ( action ) => action.source === source && action.column === column ) !== undefined;
}

function getColumnsForDropdown(
	sources: string[],
	schema: Schema[],
	filters: FilterGroup[],
	actions: ActionOption | ActionOption[]
) {
	return sources
		.map( ( source ) => {
			const sourceSchema = getSchema( schema, source );

			if ( ! sourceSchema ) {
				return null;
			}

			const columns = sourceSchema.columns.filter(
				( column ) =>
					column.column &&
					! hasFilter( filters, source, column.column ) &&
					! hasAction( actions, source, column.column )
			);

			if ( columns.length > 0 ) {
				return {
					value: source,
					label: sourceSchema.name,
					options: columns.map( ( column ) => {
						return {
							value: source + '__' + String( column.column || '' ),
							label: column.title || '',
						};
					} ),
				};
			}

			return null;
		} )
		.filter(
			( item ): item is { value: string; label: string; options: { value: string; label: string }[] } =>
				item !== null
		);
}

function showPresetValue( search: string, replace: string | null, defaults: SearchValues | null ): boolean {
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

function hasVisibleFilters( filters: FilterGroup[], tags: PresetTag[] ): boolean {
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

function hasVisibleAction(
	actionOption: ActionOption | ActionOption[],
	tags: PresetTag[],
	preset: PresetValue | null
): boolean {
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

function Form( { search, onSetSearch, isBusy, sources, preset, schema }: FormProps ) {
	const {
		searchPhrase = '',
		searchFlags = [],
		source = [],
		perPage = 25,
		replacement = '',
		filters = [],
		actionOption = [],
		view = [],
	} = search;
	const locked = preset ? preset.locked : [];
	const tags = preset ? preset.tags : [];
	const headerClassObject = getHeaderClass( tags );
	const headerClass = classnames( headerClassObject );
	const defaultValues = getDefaultPresetValues( preset );
	const filterOptions = getSearchOptionsForSources( source, schema );
	const initialFilterValue =
		filterOptions.length > 0 && typeof filterOptions[ 0 ].value === 'string' ? filterOptions[ 0 ].value : '';
	const [ currentFilter, setCurrentFilter ] = useState( initialFilterValue );
	const viewFilters = hasVisibleFilters( filters, tags ) && ! isLocked( locked, 'filters' );

	useEffect( () => {
		if ( ! filterOptions.find( ( option ) => option.value === currentFilter ) ) {
			const newValue =
				filterOptions.length > 0 && typeof filterOptions[ 0 ].value === 'string'
					? filterOptions[ 0 ].value
					: '';
			setCurrentFilter( newValue );
		}
	}, [ source, currentFilter, filterOptions ] );

	function applySources( selected: string[], changed: string ): SearchValues {
		const newSource = convertToSource( selected );
		const allowedFilters = getSearchOptionsForSources( newSource, schema ).map( ( item ) => item.value );
		const newFilters =
			newSource.indexOf( changed ) !== -1
				? getDefaultFilters( changed ).filter( ( f: FilterGroup ) => allowedFilters.indexOf( f.type ) === -1 )
				: [];

		return {
			source: newSource,
			filters: filters.filter( ( f ) => allowedFilters.indexOf( f.type ) !== -1 ).concat( newFilters ),
			actionOption: [],
			view: view.filter( ( f ) => newSource.indexOf( f.split( '__' )[ 0 ] ) !== -1 ),
		};
	}

	function addFilter() {
		const currentSchema = getSchema( schema, currentFilter );
		if ( currentSchema && typeof currentFilter === 'string' ) {
			onSetSearch( {
				filters: filters.concat( getFilterForType( currentFilter, currentSchema ) ),
			} );
		}
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
					<th>{ __( 'Source', 'search-regex' ) }</th>
					<td>
						{ ! isLocked( locked, 'source' ) && (
							<MultiOptionDropdown
								options={ getSourcesForDropdown( sources ) }
								selected={ source }
								onChange={ ( selected ) => {
									const newSource = Array.isArray( selected ) ? selected : [];
									const changed =
										newSource.find( ( s ) => ! source.includes( s ) ) || newSource[ 0 ] || '';
									onSetSearch( applySources( newSource, changed ) );
								} }
								multiple
								disabled={ isBusy }
								badges
								title={ source.length === 0 ? __( 'Source', 'search-regex' ) : '' }
								aria-label={ __( 'Sources', 'search-regex' ) }
							/>
						) }

						{ viewFilters && (
							<>
								<span>
									<strong>{ __( 'Filters', 'search-regex' ) }</strong>
								</span>
								<Select
									disabled={ isBusy }
									name="filter"
									value={ currentFilter }
									onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
										setCurrentFilter( ev.target.value )
									}
									items={ filterOptions }
								/>
								<Button onClick={ addFilter } disabled={ isBusy || filters.length >= MAX_AND_FILTERS }>
									{ __( 'Add', 'search-regex' ) }
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
						<th>{ __( 'Search', 'search-regex' ) }</th>

						<td>
							{ ! isLocked( locked, 'searchPhrase' ) && (
								<Search
									disabled={ isBusy }
									value={ searchPhrase }
									onChange={ ( value: string ) => onSetSearch( { searchPhrase: value } ) }
									multiline={ searchFlags.indexOf( 'multi' ) !== -1 }
								/>
							) }

							{ ! isLocked( locked, 'searchFlags' ) && (
								<SearchFlags
									flags={ searchFlags }
									disabled={ isBusy }
									onChange={ ( flags: string[] ) => onSetSearch( { searchFlags: flags } ) }
									allowMultiline
								/>
							) }
						</td>
					</tr>
				) }

			{ preset && (
				<TaggedPhrases
					disabled={ isBusy }
					search={ preset.search as any }
					values={ search as any }
					onChange={ ( value: any ) => onSetSearch( value ) }
					tags={ tags }
					className={ headerClass }
					key={ preset.id }
				/>
			) }

			{ ! isLocked( locked, 'action' ) && hasVisibleAction( actionOption, tags, preset ) && (
				<Actions
					locked={ locked }
					tags={ tags }
					preset={ preset }
					headerClass={ headerClass }
					searchPhrase={ searchPhrase }
					disabled={ isBusy }
					sources={ source }
					onSetSearch={ onSetSearch as any }
					search={
						{
							action: ( search as any ).action || '',
							actionOption,
							replacement: replacement || null,
						} as any
					}
				/>
			) }
			{ ( ! isLocked( locked, 'perPage' ) || ! isLocked( locked, 'view' ) ) && (
				<tr className={ classnames( 'searchregex-search__results', headerClass ) }>
					<th>{ __( 'Results', 'search-regex' ) }</th>
					<td>
						{ ! isLocked( locked, 'perPage' ) && (
							<Select
								name="perPage"
								items={ getAvailablePerPage() as any }
								value={ String( perPage ) }
								onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
									onSetSearch( { perPage: parseInt( ev.target.value, 10 ) } )
								}
								disabled={ isBusy }
							/>
						) }

						{ ! isLocked( locked, 'view' ) && source.length > 0 && (
							<MultiOptionDropdown
								options={ getColumnsForDropdown( source, schema, filters, actionOption ) }
								selected={ view }
								onChange={ ( selected ) =>
									onSetSearch( { view: Array.isArray( selected ) ? selected : [] } )
								}
								multiple
								disabled={ isBusy }
								title={ __( 'View Columns', 'search-regex' ) }
								badges
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

function mapStateToProps( state: RootState ) {
	const { sources, schema } = state.search;

	return {
		sources,
		schema,
	};
}

export default connect( mapStateToProps, null )( Form );
