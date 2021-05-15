/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';
import { useSelector, useDispatch } from 'react-redux';

/**
 * Internal dependencies
 */

import SearchForm from './search-form';
import SearchResults from './search-results';
import SearchActions from './search-actions';
import ReplaceProgress from 'component/replace-progress';
import { Notice } from 'wp-plugin-components';
import { search } from 'state/search/action';
import { STATUS_FAILED } from 'state/settings/type';
import './style.scss';

function SearchReplace( props ) {
	const { status, isSaving } = useSelector( ( state ) => state.search );
	const dispatch = useDispatch();

	function submit( ev ) {
		ev.preventDefault();

		dispatch( search( 0 ) );
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

			{ status !== null && status !== STATUS_FAILED && isSaving && <ReplaceProgress /> }
			{ status !== null && status !== STATUS_FAILED && ! isSaving && <SearchResults /> }
		</>
	);
}

export default SearchReplace;
