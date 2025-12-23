import { useState, useEffect } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Line } from 'rc-progress';
import { isAdvancedSearch } from '../../lib/search-utils';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from '../../lib/constants';
import { useSlidingActionWindow } from '../../lib/result-window';
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
	const setTotals = useSearchStore( ( state ) => state.setTotals );
	const setProgress = useSearchStore( ( state ) => state.setProgress );
	const setStatus = useSearchStore( ( state ) => state.setStatus );
	const clearResults = useSearchStore( ( state ) => state.clearResults );
	const addError = useMessageStore( ( state ) => state.addError );

	const [ requestCount, setRequestCount ] = useState( 0 );
	const performMutation = useSearch();
	// ✨ Search is already validated - no need for type assertion
	const isAdvanced = isAdvancedSearch( search );
	const total = getTotal( isAdvanced, totals );
	const { current = 0, next = 0, rows = 0 } = progress;
	const percent = Math.min(
		100,
		status === STATUS_IN_PROGRESS ? getPercent( next === false ? total : ( next as number ), total ) : 100
	);
	const canLoad = progress.next !== false && status === STATUS_IN_PROGRESS;

	const onNext = ( page: number | false, perPage: number ) => {
		if ( page === false ) {
			return;
		}

		setRequestCount( ( prev ) => prev + 1 );
		setStatus( STATUS_IN_PROGRESS );

		performMutation.mutate(
			{
				...search,
				page,
				perPage,
				save: true,
			},
			{
				onSuccess: ( data ) => {
					// ✨ Data is already validated by Zod in useSearch hook
					// Convert API results (number row_id) to Result[] (string row_id)
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
					setStatus( data.status ?? STATUS_COMPLETE );
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
	};

	// Handle export when operation completes
	useEffect( () => {
		if (
			status === STATUS_COMPLETE &&
			progress.next === false &&
			search.action === 'export' &&
			results.length > 0
		) {
			const format = search.actionOption?.format || 'json';
			saveExport( results, format );
		}
	}, [ status, progress.next, search.action, search.actionOption, results ] );

	useSlidingActionWindow(
		canLoad,
		requestCount,
		( size: number ) => onNext( progress.next as number, size ),
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
