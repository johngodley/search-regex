/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Select from 'component/select';
import MultiOptionDropdown from 'component/multi-option-dropdown';
import Replace from 'component/replace';
import { setSearch } from 'state/search/action';
import { getSearchOptions, getPerPage } from 'state/search/selector';
import { STATUS_IN_PROGRESS } from 'state/settings/type';

function getCurrentSource( sources, current ) {
	if ( current.length === 0 ) {
		return null;
	}

	for ( let index = 0; index < sources.length; index++ ) {
		for ( let subIndex = 0; subIndex < sources[ index ].sources.length; subIndex++ ) {
			if ( sources[ index ].sources[ subIndex ].name === current[ 0 ] ) {
				return sources[ index ].sources[ subIndex ];
			}
		}
	}

	return null;
}

const sourcesToSelect = ( sources ) => sources.map( ( { name, label } ) => ( { label, value: name } ) );
const objectToArray = ( object ) => Object.keys( object ).map( item => ( { value: item, label: object[ item ] } ) );

function SearchForm( { search, onSetSearch, sources, sourceFlagOptions, replaceAll } ) {
	const { searchPhrase, searchFlags, sourceFlags, source, perPage } = search;
	const options = sources.map( ( { sources: groupSources, label } ) => ( { label, value: sourcesToSelect( groupSources ) } ) );
	const sourceFlagsForSource = sourceFlagOptions[ source[ 0 ] ] ? objectToArray( sourceFlagOptions[ source[ 0 ] ] ) : null;
	const isBusy = status === STATUS_IN_PROGRESS || replaceAll;
	const currentSource = getCurrentSource( sources, source );

	const setSearchValue = ( ev ) => {
		onSetSearch( { [ ev.target.name ]: ev.target.value } );
	};

	return (
		<table>
			<tbody>
				<tr className="searchregex-search__search">
					<th>{ __( 'Search' ) }</th>

					<td>
						<input
							type="text"
							value={ searchPhrase }
							name="searchPhrase"
							placeholder={ __( 'Enter search phrase' ) }
							onChange={ setSearchValue }
							disabled={ isBusy }
						/>

						<MultiOptionDropdown
							options={ getSearchOptions() }
							selected={ searchFlags }
							onApply={ ( searchFlags ) => onSetSearch( { searchFlags } ) }
							title={ __( 'Search Flags' ) }
							isEnabled={ ! isBusy }
							badges
						/>
					</td>
				</tr>

				<tr className="searchregex-search__replace">
					<th>{ __( 'Replace' ) }</th>
					<td>
						<Replace
							canReplace={ ! isBusy }
							setReplace={ ( replacement ) => onSetSearch( { replacement } ) }
							placeholder={ __( 'Enter global replacement text' ) }
						/>
					</td>
				</tr>

				<tr className="searchregex-search__source">
					<th>{ __( 'Source' ) }</th>
					<td>
						<Select
							items={ options }
							value={ source[ 0 ] }
							onChange={ ( ev ) => onSetSearch( { source: [ ev.target.value ], sourceFlags: {} } ) }
							name="source"
							isEnabled={ ! isBusy }
						/>

						{ sourceFlagsForSource && sourceFlagsForSource.length > 0 && (
							<MultiOptionDropdown
								options={ sourceFlagsForSource }
								selected={ sourceFlags }
								onApply={ ( sourceFlags ) => onSetSearch( { sourceFlags } ) }
								title={ __( 'Source Options' ) }
								isEnabled={ ! isBusy }
								badges
								hideTitle
							/>
						) }

						{ currentSource && <span className="searchregex-search__source__description">{ currentSource.description }</span> }
					</td>
				</tr>

				<tr>
					<th>{ __( 'Results' ) }</th>
					<td>
						<Select
							name="perPage"
							items={ getPerPage() }
							value={ perPage }
							onChange={ ( ev ) => onSetSearch( { perPage: parseInt( ev.target.value, 10 ) } ) }
							isEnabled={ ! isBusy }
						/>
					</td>
				</tr>
			</tbody>
		</table>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onSetSearch: ( searchValue ) => {
			dispatch( setSearch( searchValue ) );
		},
	};
}

function mapStateToProps( state ) {
	const { search, sources, sourceFlags, replaceAll } = state.search;

	return {
		search,
		sources,
		sourceFlagOptions: sourceFlags,
		replaceAll,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchForm );
