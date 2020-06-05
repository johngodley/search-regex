/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-library/lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { Select, MultiOptionDropdown } from 'wp-plugin-library';
import Replace from 'component/replace';
import { setSearch } from 'state/search/action';
import { getAvailableSearchFlags, getAvailablePerPage } from 'state/search/selector';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import { getAllPostTypes } from 'lib/sources';

function convertToSource( options, changed, group, sources ) {
	let newSources = [];
	const allPostTypes = getAllPostTypes( sources );

	Object.keys( options ).forEach( option => {
		newSources = newSources.concat( options[ option ] );
	} );

	if ( changed === 'posts' ) {
		if ( newSources.indexOf( 'posts' ) === -1 ) {
			newSources = newSources.filter( item => allPostTypes.indexOf( item ) === -1 );
		} else {
			newSources = newSources.concat( allPostTypes );
		}
	} else if ( group === 'posttype' && newSources.indexOf( changed ) === -1 ) {
		newSources = newSources.filter( item => item !== 'posts' );
	} else if ( group === 'posttype' && allPostTypes.filter( item => newSources.indexOf( item ) !== -1 ).length === allPostTypes.length ) {
		newSources.push( 'posts' );
	}

	if ( newSources.length === 0 ) {
		newSources = [ 'post', 'page' ];
	}

	return Array.from( new Set( newSources ) );
}

function convertSelectedSource( source, sources ) {
	const newSources = {};

	for ( let index = 0; index < sources.length; index++ ) {
		const currentSource = sources[ index ];

		if ( ! newSources[ currentSource.name ] ) {
			newSources[ currentSource.name ] = [];
		}

		// source is an array
		for ( let index = 0; index < source.length; index++ ) {
			const inSources = currentSource.sources.find( item => item.name === source[ index ] );

			if ( inSources ) {
				newSources[ currentSource.name ].push( source[ index ] );
			}
		}
	}

	return newSources;
}

function getSourcesForDropdown( sources ) {
	return sources.map( sourceGroup => {
		return {
			label: sourceGroup.label,
			value: sourceGroup.name,
			multiple: true,
			options: sourceGroup.sources.map( ( { label, name } ) => {
				return {
					label,
					value: name,
				}
			} ),
		};
	} );
}

function customBadge( badges, sources ) {
	// If 'all post types' then dont show the post types
	if ( badges.indexOf( 'posts' ) !== -1 ) {
		const allPosts = getAllPostTypes( sources );

		return badges.filter( item => allPosts.indexOf( item ) === -1 );
	}

	return badges;
}

function getSourceFlagOptions( options, source ) {
	let sourceFlags = [];

	Object.keys( options ).forEach( option => {
		if ( source.indexOf( option ) !== -1 ) {
			const newFlags = Object.keys( options[ option ] ).map( item => ( {
				label: options[ option ][ item ],
				value: item,
			} ) );

			for ( let index = 0; index < newFlags.length; index++ ) {
				if ( ! sourceFlags.find( item => item.value === newFlags[ index ].value ) ) {
					sourceFlags.push( newFlags[ index ] );
				}
			}
		}
	} );

	return sourceFlags;
}

function SearchForm( { search, onSetSearch, sources, sourceFlagOptions, replaceAll, status } ) {
	const { searchPhrase, searchFlags, sourceFlags, source, perPage } = search;
	const sourceFlagsForSource = getSourceFlagOptions( sourceFlagOptions, source );
	const isBusy = status === STATUS_IN_PROGRESS || replaceAll;

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
							options={ getAvailableSearchFlags() }
							selected={ searchFlags }
							onApply={ ( searchFlags ) => onSetSearch( { searchFlags } ) }
							title={ __( 'Search Flags' ) }
							disabled={ isBusy }
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
						<MultiOptionDropdown
							options={ getSourcesForDropdown( sources ) }
							selected={ convertSelectedSource( source, sources ) }
							onApply={ ( newSources, change, group ) => onSetSearch( { source: convertToSource( newSources, change, group, sources ) } ) }
							title=""
							disabled={ isBusy }
							badges
							badgeValues
							customBadge={ ( badges ) => customBadge( badges, sources ) }
						/>

						{ Object.keys( sourceFlagsForSource ).length > 0 && (
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

				<tr>
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
	const { search, sources, sourceFlags, replaceAll, status } = state.search;

	return {
		search,
		sources,
		sourceFlagOptions: sourceFlags,
		replaceAll,
		status,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchForm );
