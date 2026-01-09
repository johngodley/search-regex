import { __ } from '@wordpress/i18n';
import { FormEvent } from 'react';
import SearchForm from './search-form';
import SearchResults from './search-results';
import SearchActions from './search-actions';
import ReplaceProgress from '../../component/replace-progress';
import { Notice } from '@wp-plugin-components';
import {
	useSearchStore,
	convertToSearchTotals,
	convertToSearchProgress,
	convertToResults,
} from '../../stores/search-store';
import { useSearch } from '../../hooks/use-search';
import { STATUS_FAILED, STATUS_COMPLETE, STATUS_IN_PROGRESS, SEARCH_FORWARD } from '../../lib/constants';
import './style.scss';

function SearchReplace() {
	const status = useSearchStore( ( state ) => state.status );
	const isSaving = useSearchStore( ( state ) => state.isSaving );
	const searchValues = useSearchStore( ( state ) => state.search );
	const searchDirection = useSearchStore( ( state ) => state.searchDirection );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setShowLoading = useSearchStore( ( state ) => state.setShowLoading );
	const setCanCancel = useSearchStore( ( state ) => state.setCanCancel );
	const setResultsDirty = useSearchStore( ( state ) => state.setResultsDirty );
	const setSearchDirection = useSearchStore( ( state ) => state.setSearchDirection );
	const updateSearchUrl = useSearchStore( ( state ) => state.updateSearchUrl );

	const searchMutation = useSearch();

	function submit( ev: FormEvent< HTMLFormElement > ) {
		ev.preventDefault();

		// Clear previous results
		setResults( [] );
		setResultsDirty( false );
		setShowLoading( true );
		setCanCancel( true );

		// Set search direction to forward for new searches
		setSearchDirection( SEARCH_FORWARD );

		// Update URL with search parameters
		updateSearchUrl();

		setStatus( STATUS_IN_PROGRESS );

		// Perform search
		searchMutation.mutate(
			{
				...searchValues,
				page: 0,
				searchDirection: searchDirection || SEARCH_FORWARD,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Convert API results (number row_id) to Result[] (string row_id)
					setResults( convertToResults( data.results ) );
					setTotals( convertToSearchTotals( data.totals ) );
					setProgress( convertToSearchProgress( data.progress ) );
					setStatus( data.status ?? STATUS_COMPLETE );
					setShowLoading( false );
					setCanCancel( false );
				},
				onError: () => {
					setStatus( STATUS_FAILED );
					setShowLoading( false );
					setCanCancel( false );
				},
			}
		);
	}

	return (
		<>
			<Notice level="warning">
				<p>{ __( 'Please backup your data before making modifications.', 'search-regex' ) }</p>
			</Notice>

			<p>{ __( 'Search and replace information in your database.', 'search-regex' ) }</p>

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
