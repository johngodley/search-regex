import { useState, useEffect, useRef } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Line } from 'rc-progress';
import { isAdvancedSearch } from '../../lib/search-utils';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from '../../lib/constants';
import { useSlidingSearchWindow } from '../../lib/result-window';
import { useSearchStore, convertToResults } from '../../stores/search-store';
import { useSearch } from '../../hooks/use-search';
import { useMessageStore } from '../../stores/message-store';
import { saveExport } from '../../lib/export';
import './style.scss';

interface CustomTotal {
	name: string;
	value: number;
}

interface Totals {
	rows: number;
	matched_rows: number;
	custom?: CustomTotal[];
}

const getTotal = ( isAdvanced: boolean, totals: Totals ): number => ( isAdvanced ? totals.rows : totals.matched_rows );
const getPercent = ( current: number, total: number ): number =>
	total > 0 ? Math.round( ( current / total ) * 100 ) : 0;

function getTotalCount( name: string, count: number ): string {
	const formattedCount = new Intl.NumberFormat( SearchRegexi10n.locale ).format( count );

	if ( name === 'delete' ) {
		/* translators: %s: number of rows deleted */
		return sprintf( _n( '%s row deleted.', '%s rows deleted.', count, 'search-regex' ), formattedCount );
	}

	/* translators: %s: number of rows */
	return sprintf( _n( '%s row.', '%s rows.', count, 'search-regex' ), formattedCount );
}

function Totals( { totals, current }: { totals: Totals; current: number } ): JSX.Element {
	const { custom = [] } = totals;

	if ( custom.length > 0 ) {
		return (
			<>
				{ custom.map( ( item ) => (
					<p key={ item.name }>{ getTotalCount( item.name, item.value ) }</p>
				) ) }
			</>
		);
	}

	if ( current === 0 ) {
		return <p>&nbsp;</p>;
	}

	return <p>{ getTotalCount( 'rows', current ) }</p>;
}

function ReplaceProgress(): JSX.Element {
	const progress = useSearchStore( ( state ) => state.progress );
	const totals = useSearchStore( ( state ) => state.totals );
	const status = useSearchStore( ( state ) => state.status );
	const search = useSearchStore( ( state ) => state.search );
	const results = useSearchStore( ( state ) => state.results );
	const setResults = useSearchStore( ( state ) => state.setResults );
	const exportData = useSearchStore( ( state ) => state.exportData );
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const setIsSaving = useSearchStore( ( state ) => state.setIsSaving );
	const setCanCancel = useSearchStore( ( state ) => state.setCanCancel );
	const setReplaceAll = useSearchStore( ( state ) => state.setReplaceAll );
	const clearResults = useSearchStore( ( state ) => state.clearResults );
	const addError = useMessageStore( ( state ) => state.addError );

	const [ requestCount, setRequestCount ] = useState( 0 );
	const exportSavedRef = useRef( false );
	const performMutation = useSearch();
	// ✨ Search is already validated - no need for type assertion
	const isAdvanced = isAdvancedSearch( search );
	const total = getTotal( isAdvanced, totals );
	const { current = 0, next = 0, rows = 0 } = progress;

	// Calculate progress percentage based on current position in database
	// For regex: current = offset processed, total = totals.rows (total DB rows)
	// When complete (next = false AND status = COMPLETE), show 100%
	const percent = Math.min( 100, status === STATUS_COMPLETE && next === false ? 100 : getPercent( current, total ) );

	// Initialize request count when replace operation starts
	useEffect( () => {
		if ( status === STATUS_IN_PROGRESS && progress.next !== false && requestCount === 0 ) {
			setRequestCount( 1 );
		}
	}, [ status, progress.next, requestCount ] );

	const onReplaceMore = ( page: number | false, pageSize: number ) => {
		if ( page === false ) {
			return;
		}

		setStatus( STATUS_IN_PROGRESS );

		performMutation.mutate(
			{
				...search,
				page,
				save: true,
				perPage: pageSize,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Export results are accumulated in useSearch; data.results is empty for exports
					setResults( [ ...results, ...convertToResults( data.results ) ] );
					setTotals( {
						matched_rows: data.totals.matched_rows,
						rows: data.totals.rows,
						...( data.totals.custom ? { custom: data.totals.custom } : {} ),
					} );
					setProgress( {
						next: data.progress.next,
						...( data.progress.current !== undefined ? { current: data.progress.current } : {} ),
						...( data.progress.rows !== undefined ? { rows: data.progress.rows } : {} ),
						...( data.progress.previous !== undefined ? { previous: data.progress.previous } : {} ),
					} );

					// Keep status as IN_PROGRESS if more pages, otherwise complete
					const hasMorePages = data.progress.next !== false;
					setStatus( hasMorePages ? STATUS_IN_PROGRESS : data.status ?? STATUS_COMPLETE );

					// Increment request count to trigger next iteration
					setRequestCount( ( prev ) => prev + 1 );
				},
				onError: () => {
					setStatus( STATUS_FAILED );
				},
			}
		);
	};

	const onError = () => {
		addError( __( 'Your search resulted in too many requests. Please narrow your search terms.', 'search-regex' ) );
	};

	const onClear = () => {
		clearResults();
		setIsSaving( false );
		setCanCancel( false );
		setReplaceAll( false );
	};

	// Determine if we should continue loading (sliding window)
	const hasMorePages = progress.next !== false;
	const canLoad = hasMorePages && status === STATUS_IN_PROGRESS && requestCount > 0;
	const perPageValue = search.perPage ?? 200;

	// Clean up flags when operation fails (not on success - let user click Finished button)
	useEffect( () => {
		if ( status === STATUS_FAILED ) {
			setIsSaving( false );
			setCanCancel( false );
			setReplaceAll( false );
		}
	}, [ status, setIsSaving, setCanCancel, setReplaceAll ] );

	// Handle export when operation completes - use a ref to ensure it only fires once
	// Reading actionOption from the store at fire time (not as a dependency) so that
	// changing the format after completion does not re-trigger a download.
	useEffect( () => {
		if (
			status === STATUS_COMPLETE &&
			progress.next === false &&
			search.action === 'export' &&
			exportData.length > 0 &&
			! exportSavedRef.current
		) {
			exportSavedRef.current = true;
			const { actionOption } = useSearchStore.getState().search;
			const format = ( actionOption as { format?: string } )?.format || 'json';
			saveExport( exportData, format );
		}
	}, [ status, progress.next, search.action, exportData ] );

	// Use sliding window for replace all - same as search but with save=true
	useSlidingSearchWindow(
		canLoad,
		requestCount,
		perPageValue,
		( size: number ) => {
			// Get fresh progress value from store to avoid stale closure
			const currentProgress = useSearchStore.getState().progress;
			const nextPage = currentProgress.next;

			onReplaceMore( nextPage as number, size );
		},
		onError
	);

	return (
		<div className="searchregex-replaceall">
			<h3>{ __( 'Progress', 'search-regex' ) }</h3>

			<div className="searchregex-replaceall__progress">
				<div className="searchregex-replaceall__container">
					<Line percent={ percent } strokeWidth={ 4 } trailWidth={ 4 } strokeLinecap="square" />
				</div>

				<div className="searchregex-replaceall__status">{ `${ percent }%` }</div>
			</div>

			<div className="searchregex-replaceall__stats">
				<Totals totals={ totals } current={ isAdvanced ? current : current + rows } />

				{ status === STATUS_COMPLETE && (
					<button type="button" className="button button-primary" onClick={ onClear }>
						{ __( 'Finished!', 'search-regex' ) }
					</button>
				) }
			</div>
		</div>
	);
}

export default ReplaceProgress;
