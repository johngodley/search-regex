import { __, _n, sprintf } from '@wordpress/i18n';
import { Line } from 'rc-progress';
import { connect } from 'react-redux';
import type { ThunkDispatch } from 'redux-thunk';
import { clear, performMore, setError } from '../../state/search/action';
import { isAdvancedSearch } from '../../state/search/selector';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE } from '../../state/settings/type';
import { useSlidingActionWindow } from '../../lib/result-window';
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

interface Progress {
	current?: number;
	next: number | false;
	rows?: number;
}

interface ReplaceProgressOwnProps {
	progress: Progress;
	totals: Totals;
	requestCount: number;
	status: string;
	isAdvanced: boolean;
}

interface ReplaceProgressDispatchProps {
	onNext: ( page: number | false, perPage: number ) => void;
	onClear: () => void;
	onError: () => void;
}

type ReplaceProgressProps = ReplaceProgressOwnProps & ReplaceProgressDispatchProps;

interface RootState {
	search: {
		progress: Progress;
		totals: Totals;
		requestCount: number;
		status: string;
		search: unknown;
	};
}

const getTotal = ( isAdvanced: boolean, totals: Totals ): number => ( isAdvanced ? totals.rows : totals.matched_rows );
const getPercent = ( current: number, total: number ): number =>
	total > 0 ? Math.round( ( current / total ) * 100 ) : 0;

function getTotalCount( name: string, count: number ): string {
	const formattedCount = new Intl.NumberFormat( ( window as any ).SearchRegexi10n.locale as string ).format( count );

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

function ReplaceProgress( props: ReplaceProgressProps ): JSX.Element {
	const { progress, totals, requestCount, onNext, status, onClear, isAdvanced, onError } = props;
	const total = getTotal( isAdvanced, totals );
	const { current = 0, next = 0, rows = 0 } = progress;
	const percent = Math.min(
		100,
		status === STATUS_IN_PROGRESS ? getPercent( next === false ? total : next, total ) : 100
	);
	const canLoad = progress.next !== false && status === STATUS_IN_PROGRESS;

	useSlidingActionWindow( canLoad, requestCount, ( size: number ) => onNext( progress.next, size ), onError );

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

function mapStateToProps( state: RootState ): ReplaceProgressOwnProps {
	const { progress, totals, requestCount, status, search } = state.search;

	return {
		status,
		progress,
		totals,
		requestCount,
		isAdvanced: isAdvancedSearch( search as any ),
	};
}

function mapDispatchToProps( dispatch: ThunkDispatch< RootState, unknown, any > ): ReplaceProgressDispatchProps {
	return {
		onClear: () => {
			dispatch( clear() );
		},
		onNext: ( page: number | false, perPage: number ) => {
			if ( page !== false ) {
				void dispatch( performMore( page, perPage ) );
			}
		},
		onError: () => {
			dispatch(
				setError(
					__( 'Your search resulted in too many requests. Please narrow your search terms.', 'search-regex' )
				)
			);
		},
	};
}

export default connect< ReplaceProgressOwnProps, ReplaceProgressDispatchProps, Record< string, never >, RootState >(
	mapStateToProps,
	mapDispatchToProps
)( ReplaceProgress );
