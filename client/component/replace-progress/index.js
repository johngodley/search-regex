/**
 * External dependencies
 */

import React, { useEffect } from 'react';
import { translate as __, numberFormat } from 'lib/locale';
import { Line } from 'rc-progress';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { clear, replaceNext } from 'state/search/action';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE } from 'state/settings/type';
import './style.scss';

function ReplaceProgress( props ) {
	const { progress, totals, requestCount, replaceCount, onNext, status, onCancel, phraseCount, isRegex } = props;
	const total = isRegex ? totals.rows : totals.matched_rows;
	const current = progress.rows === undefined ? 0 : progress.current + progress.rows;
	const percent = total > 0 ? Math.round( ( current / total ) * 100 ) : 0;

	useEffect( () => {
		if ( requestCount > 0 && progress.next !== false && replaceCount < total && status === STATUS_IN_PROGRESS ) {
			onNext( progress.next );
		}
	}, [ requestCount ] );

	return (
		<div className="searchregex-replaceall">
			<h3>{ __( 'Replace progress' ) }</h3>

			<div className="searchregex-replaceall__progress">
				<Line percent={ percent } strokeWidth="4" trailWidth="4" strokeLinecap="square" />

				<div className="searchregex-replaceall__status">{ `${ percent }%` }</div>
			</div>

			{ status === STATUS_COMPLETE && (
				<>
					<p>{ __( 'Finished!' ) }</p>
					<p>{ __( 'Rows updated: %s', { args: numberFormat( replaceCount ) } ) }</p>
					<p>{ __( 'Phrases replaced: %s', { args: numberFormat( phraseCount ) } ) }</p>
					<button className="button button-primary" onClick={ onCancel }>{ __( 'Finished!' ) }</button>
				</>
			) }
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
		isRegex: search.searchFlags.regex !== undefined,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		onCancel: () => {
			dispatch( clear() );
		},
		onNext: ( page ) => {
			dispatch( replaceNext( page ) );
		},
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ReplaceProgress );
