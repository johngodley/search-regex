/**
 * External dependencies
 */

import React, { useState, useEffect } from 'react';
import { translate as __, numberFormat } from 'lib/locale';
import classnames from 'classnames';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import Actions from './actions';
import Spinner from 'component/spinner';
import ResultColumns from './result-columns';
import Editor from 'component/editor';
import ExternalLink from 'component/external-link';
import './style.scss';

function getReplacement( globalReplacement, replacement ) {
	if ( replacement === null || replacement.length > 0 ) {
		return replacement;
	}

	return globalReplacement;
}

function ResultTitle( { view, title } ) {
	if ( view ) {
		return <ExternalLink url={ view }>{ title }</ExternalLink>;
	}

	return title;
}

function Result( props ) {
	const { result, globalReplacement, replacing, actionDropdown } = props;
	const { columns, actions, row_id, source_name, source_type, title, match_count } = result;
	const isReplacing = replacing && replacing.indexOf( row_id ) !== -1;

	// State
	const [ replacement, setReplacement ] = useState( '' );
	const [ editor, setEditor ] = useState( false );

	// If global changes, reset any local state
	useEffect( () => {
		setReplacement( '' );
	}, [ globalReplacement ] );

	return (
		<tr className={ classnames( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
			<td className="searchregex-result__table">
				<span title={ source_type }>{ source_name }</span>
			</td>
			<td className="searchregex-result__row">
				{ numberFormat( row_id ) }
			</td>

			<td className="searchregex-result__row">
				{ match_count }
			</td>

			<td className="searchregex-result__match">
				<h2><ResultTitle view={ actions.view } title={ title } /></h2>

				{ columns.map( ( column ) => (
					<ResultColumns
						column={ column }
						replacement={ getReplacement( globalReplacement, replacement ) }
						rowId={ row_id }
						isReplacing={ isReplacing }
						sourceType={ source_type }
						key={ column.column_id }
					/>
				) ) }
			</td>

			<td className={ classnames( 'searchregex-result__action', actionDropdown && 'searchregex-result__action__dropdown' ) }>
				{ isReplacing ? <Spinner /> : (
					<Actions
						actions={ actions }
						setReplacement={ setReplacement }
						result={ result }
						onEditor={ () => setEditor( true ) }
						sourceType={ source_type }
						actionDropdown={ actionDropdown }
						description={ __( 'Replace %(count)s match.', 'Replace %(count)s matches.', {
							count: match_count,
							args: {
								count: numberFormat( match_count ),
							},
						} ) }
					/>
				) }

				{ editor && (
					<Editor
						onClose={ () => setEditor( false ) }
						result={ result }
					/>
				) }
			</td>
		</tr>
	);
}

function mapStateToProps( state ) {
	const { replacing, search } = state.search;
	const { actionDropdown } = state.settings.values;

	return {
		replacing,
		globalReplacement: search.replacement,
		actionDropdown,
	};
}

export default connect(
	mapStateToProps,
	null
)( Result );
