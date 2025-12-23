import { __ } from '@wordpress/i18n';
import { Spinner, Button } from '@wp-plugin-components';
import { STATUS_IN_PROGRESS, STATUS_FAILED, STATUS_COMPLETE } from '../../lib/constants';
import {
	useSearchStore,
	convertToSearchTotals,
	convertToSearchProgress,
	convertToResults,
} from '../../stores/search-store';
import { useSearch } from '../../hooks/use-search';

interface ActionOption {
	length?: number;
	hook?: string;
}

function isPerformReady( action: string, actionOption: ActionOption, replacement: string | null ): boolean {
	if ( action === 'replace' && replacement === null ) {
		return true;
	}

	if ( action === 'replace' && replacement !== null && replacement.length > 0 ) {
		return true;
	}

	if ( action === 'delete' ) {
		return true;
	}

	if ( action === 'modify' ) {
		return actionOption.length !== undefined && actionOption.length > 0;
	}

	if ( action === 'action' ) {
		return actionOption.hook !== undefined && actionOption.hook.length > 0;
	}

	if ( action === 'export' || action === 'global' ) {
		return true;
	}

	return false;
}

function getPerformButton( action: string ): string {
	if ( action === 'delete' ) {
		return __( 'Delete Matching Rows', 'search-regex' );
	}

	if ( action === 'export' ) {
		return __( 'Export Matches', 'search-regex' );
	}

	if ( action === 'action' ) {
		return __( 'Run Action', 'search-regex' );
	}

	return __( 'Replace All', 'search-regex' );
}

function SearchActions() {
	const search = useSearchStore( ( state ) => state.search );
	const status = useSearchStore( ( state ) => state.status );
	const canCancel = useSearchStore( ( state ) => state.canCancel );
	const resultsDirty = useSearchStore( ( state ) => state.resultsDirty );
	const isSaving = useSearchStore( ( state ) => state.isSaving );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setIsSaving = useSearchStore( ( state ) => state.setIsSaving );
	const setCanCancel = useSearchStore( ( state ) => state.setCanCancel );
	const setReplaceAll = useSearchStore( ( state ) => state.setReplaceAll );
	const mode = useSearchStore( ( state ) => state.mode );

	const { action: rawAction = '', actionOption = {}, replacement = null } = search;
	const effectiveAction = mode === 'simple' ? 'replace' : rawAction;
	const effectiveActionOption = mode === 'simple' ? {} : ( actionOption as ActionOption );
	const performMutation = useSearch();

	const handlePerform = () => {
		setIsSaving( true );
		setCanCancel( true );
		setReplaceAll( true );
		setStatus( STATUS_IN_PROGRESS );

		const payload =
			mode === 'simple'
				? {
					...search,
					action: 'replace',
					actionOption: {},
				}
				: search;

		performMutation.mutate(
			{
				...payload,
				page: 0,
				save: true,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Convert API results (number row_id) to Result[] (string row_id)
					setResults( convertToResults( data.results ) );
					setTotals( convertToSearchTotals( data.totals ) );
					setProgress( convertToSearchProgress( data.progress ) );
					setStatus( data.status ?? STATUS_COMPLETE );
					setIsSaving( false );
					setCanCancel( false );
					setReplaceAll( false );
				},
				onError: () => {
					setStatus( STATUS_FAILED );
					setIsSaving( false );
					setCanCancel( false );
					setReplaceAll( false );
				},
			}
		);
	};

	const handleCancel = () => {
		setCanCancel( false );
		setIsSaving( false );
		setReplaceAll( false );
		setStatus( null );
	};

	return (
		<div className="searchregex-search__action">
			<Button isPrimary isSubmit disabled={ status === STATUS_IN_PROGRESS || isSaving } name="search">
				{ resultsDirty ? __( 'Refresh', 'search-regex' ) : __( 'Search', 'search-regex' ) }
			</Button>

			{ effectiveAction !== '' && (
				<Button
					isDestructive
					disabled={
						! isPerformReady( effectiveAction, effectiveActionOption, replacement ) ||
						status === STATUS_IN_PROGRESS ||
						isSaving
					}
					onClick={ handlePerform }
				>
					{ getPerformButton( effectiveAction ) }
				</Button>
			) }

			{ ( status === STATUS_IN_PROGRESS || isSaving ) && canCancel && (
				<>
					&nbsp;
					<Button isDestructive onClick={ handleCancel }>
						{ __( 'Cancel', 'search-regex' ) }
					</Button>
					<Spinner />
				</>
			) }
		</div>
	);
}

export default SearchActions;
