/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { translate as __, numberFormat } from 'i18n-calypso';
import { Line } from 'rc-progress';
import { connect } from 'react-redux';
import { useDelta } from 'react-delta';

/**
 * Internal dependencies
 */

import { throttle, adjustPerPage } from 'lib/result-window';
import { clear, replaceNext } from 'state/search/action';
import { isAdvancedSearch } from 'state/search/selector';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE } from 'state/settings/type';
import './style.scss';

const DEFAULT_WINDOW_SIZE = 200;

const getTotal = ( isRegex, totals ) => isRegex ? totals.rows : totals.matched_rows;
const getPercent = ( current, total ) => total > 0 ? Math.round( ( current / total ) * 100 ) : 0

function ReplaceProgress( props ) {
	const { progress, totals, requestCount, replaceCount, onNext, status, onClear, phraseCount, isRegex } = props;
	const total = getTotal( isRegex, totals );
	const current = progress.current === undefined ? 0 : progress.current;
	const percent = Math.min( 100, status === STATUS_IN_PROGRESS ? getPercent( current, total ) : 100 );
	const deltaCount = useDelta( replaceCount );
	const [ windowPage, setWindowPage ] = useState( 0 );

	useEffect( () => {
		if ( requestCount > 0 && progress.next !== false && status === STATUS_IN_PROGRESS ) {
			if ( deltaCount && deltaCount.prev && deltaCount.prev < deltaCount.curr ) {
				// Made a replace - scale down the window
				setWindowPage( Math.max( 0, windowPage - 5 ) );
			} else {
				// No replacements, scale up the window
				setWindowPage( windowPage + 1 );
			}

			throttle( () => onNext( progress.next, adjustPerPage( windowPage, DEFAULT_WINDOW_SIZE ) ) );
		}
	}, [ requestCount ] );

	return (
		<div className="searchregex-replaceall">
			<h3>{ __( 'Replace progress' ) }</h3>

			<div className="searchregex-replaceall__progress">
				<div className="searchregex-replaceall__container">
					<Line percent={ percent } strokeWidth={ 4 } trailWidth={ 4 } strokeLinecap="square" />
				</div>

				<div className="searchregex-replaceall__status">{ `${ percent }%` }</div>
			</div>

			<div className="searchregex-replaceall__stats">
				<h4>{ __( 'Replace Information' ) }</h4>
				<p>
					{ __( '%s phrase.', '%s phrases.', {
						count: phraseCount,
						args: numberFormat( phraseCount, 0 ),
					} ) }
					&nbsp;
					{ __( '%s row.', '%s rows.', {
						count: replaceCount,
						args: numberFormat( replaceCount, 0 ),
					} ) }
				</p>
				{ status === STATUS_COMPLETE && (
					<button type="button" className="button button-primary" onClick={ onClear }>{ __( 'Finished!' ) }</button>
				) }
			</div>
		</div>
	)
}

function mapStateToProps( state ) {
	const { progress, totals, requestCount, replaceCount, phraseCount, status, search } = state.search;

	return {
		status,
		progress,
		totals,
		requestCount,
		replaceCount,
		phraseCount,
		isRegex: isAdvancedSearch( search.searchFlags ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onClear: () => {
			dispatch( clear() );
		},
		onNext: ( page, perPage ) => {
			dispatch( replaceNext( page, perPage ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ReplaceProgress );
