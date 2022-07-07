/**
 * External dependencies
 */

import { __, numberFormat } from '@wordpress/i18n';
import { Line } from 'rc-progress';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { clear, performMore, setError } from '../../state/search/action';
import { isAdvancedSearch } from '../../state/search/selector';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE } from '../../state/settings/type';
import { useSlidingActionWindow } from '../../lib/result-window';
import './style.scss';

const getTotal = ( isAdvanced, totals ) => ( isAdvanced ? totals.rows : totals.matched_rows );
const getPercent = ( current, total ) => ( total > 0 ? Math.round( ( current / total ) * 100 ) : 0 );

function getTotalCount( name, count ) {
	const args = {
		count: count,
		args: numberFormat( count, 0 ),
	};

	if ( name === 'delete' ) {
		return __( '%s row deleted.', '%s rows deleted.', args );
	}

	return __( '%s row.', '%s rows.', args );
}

function Totals( { totals, current } ) {
	const { custom = [] } = totals;

	if ( custom.length > 0 ) {
		return custom.map( ( item ) => <p key={ item.name }>{ getTotalCount( item.name, item.value ) }</p> );
	}

	if ( current === 0 ) {
		return <p>&nbsp;</p>;
	}

	// Just display number of rows
	return <p>{ getTotalCount( 'rows', current ) }</p>;
}

function ReplaceProgress( props ) {
	const { progress, totals, requestCount, onNext, status, onClear, isAdvanced, onError } = props;
	const total = getTotal( isAdvanced, totals );
	const { current = 0, next = 0 } = progress;
	const percent = Math.min( 100, status === STATUS_IN_PROGRESS ? getPercent( next === false ? total : next, total ) : 100 );
	const canLoad = progress.next !== false && status === STATUS_IN_PROGRESS;

	useSlidingActionWindow( canLoad, requestCount, ( size ) => onNext( progress.next, size ), onError );

	return (
		<div className="searchregex-replaceall">
			<h3>{ __( 'Progress' ) }</h3>

			<div className="searchregex-replaceall__progress">
				<div className="searchregex-replaceall__container">
					<Line percent={ percent } strokeWidth={ 4 } trailWidth={ 4 } strokeLinecap="square" />
				</div>

				<div className="searchregex-replaceall__status">{ `${ percent }%` }</div>
			</div>

			<div className="searchregex-replaceall__stats">
				<Totals totals={ totals } current={ current } />

				{ status === STATUS_COMPLETE && (
					<button type="button" className="button button-primary" onClick={ onClear }>
						{ __( 'Finished!' ) }
					</button>
				) }
			</div>
		</div>
	);
}

function mapStateToProps( state ) {
	const { progress, totals, requestCount, status, search } = state.search;

	return {
		status,
		progress,
		totals,

		requestCount,

		isAdvanced: isAdvancedSearch( search ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onClear: () => {
			dispatch( clear() );
		},
		onNext: ( page, perPage ) => {
			dispatch( performMore( page, perPage ) );
		},
		onError: () => {
			dispatch(
				setError( __( 'Your search resulted in too many requests. Please narrow your search terms.' ) )
			);
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ReplaceProgress );
