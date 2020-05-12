/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import SearchForm from './search-form';
import SearchResults from './search-results';
import SearchActions from './search-actions';
import ReplaceProgress from 'component/replace-progress';
import './style.scss';

function SearchReplace( props ) {
	const { status, replaceAll } = props;

	return (
		<>
			<div className="inline-notice inline-warning">
				<p>{ __( 'Please backup your data before making modifications.' ) }</p>
			</div>

			<p>{ __( 'Search and replace information in your database.' ) }</p>

			<form className="searchregex-search" onSubmit={ ( ev ) => ev.preventDefault() }>
				<SearchForm />
				<SearchActions />
			</form>

			{ status && (
				replaceAll ? <ReplaceProgress /> : <SearchResults />
			) }
		</>
	);
}

function mapStateToProps( state ) {
	const { status, replaceAll } = state.search;

	return {
		status,
		replaceAll,
	};
}

export default connect(
	mapStateToProps,
	null
)( SearchReplace );
