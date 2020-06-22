/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import SearchForm from './search-form';
import SearchResults from './search-results';
import SearchActions from './search-actions';
import ReplaceProgress from 'component/replace-progress';
import { Notice } from 'wp-plugin-components';
import { search } from 'state/search/action';
import { STATUS_IN_PROGRESS } from 'state/settings/type';
import './style.scss';

const canSearch = ( status, searchPhrase ) => status === STATUS_IN_PROGRESS || searchPhrase.length === 0;

function SearchReplace( props ) {
	const { status, replaceAll, onSearch, searchPhrase } = props;

	function submit( ev ) {
		ev.preventDefault();

		if ( canSearch( status, searchPhrase ) ) {
			onSearch( searchPhrase, status );
		}
	}

	return (
		<>
			<Notice level="warning">
				<p>{ __( 'Please backup your data before making modifications.' ) }</p>
			</Notice>

			<p>{ __( 'Search and replace information in your database.' ) }</p>

			<form className="searchregex-search" onSubmit={ ( ev ) => submit( ev ) }>
				<SearchForm />
				<SearchActions />
			</form>

			{ status && ( replaceAll ? <ReplaceProgress /> : <SearchResults /> ) }
		</>
	);
}

function mapStateToProps( state ) {
	const { status, replaceAll, search } = state.search;

	return {
		status,
		replaceAll,
		searchPhrase: search.searchPhrase,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onSearch: () => {
			dispatch( search( 0 ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SearchReplace );
