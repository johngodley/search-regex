/**
 * External dependencies
 */

import { __, numberFormat } from '@wordpress/i18n';
import classnames from 'classnames';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import ResultColumn from './column';
import ResultTitle from './result-title';
import Actions from './actions';
import { getSchema, getSchemaColumn } from '../../state/search/selector';
import { getReplacedColumn } from './modify-column';
import './style.scss';

/** @typedef {import('../../state/search/type.js').Result} Result */

/**
 *
 * @param {*} globalReplacement
 * @param {*} replacement
 */
function getReplacement( globalReplacement, column, schema ) {
	if ( globalReplacement && globalReplacement.length > 0 && globalReplacement[ 0 ].column === 'global' ) {
		const globalColumn = schema.columns.find( ( item ) => item.column === column.column_id );

		if ( globalColumn && globalColumn.global ) {
			return getReplacedColumn( column, globalReplacement[ 0 ], schema );
		}
	}

	// Find column in global
	const globalColumn = globalReplacement.find( ( item ) => item.column === column.column_id );
	if ( globalColumn ) {
		return getReplacedColumn( column, globalColumn, schema );
	}

	return column;
}

/**
 *
 * @param {object} props - Component props
 * @param {Result} props.result - Result
 * @param {import('../state/search/type').Schema} props.schema - Schema
 */
function Result( props ) {
	const { result, globalReplacement, isReplacing, schema } = props;
	const { columns, actions, row_id, source_name, source_type, title } = result;

	return (
		<tr className={ classnames( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
			<td className="searchregex-result__table">
				<span title={ source_type }>{ source_name }</span>
			</td>
			<td className="searchregex-result__row">{ numberFormat( row_id, 0 ) }</td>

			<td className="searchregex-result__match">
				<h2>
					<ResultTitle view={ actions.view } title={ title } />
				</h2>

				{ columns.map( ( column ) => (
					<ResultColumn
						column={ getReplacement( globalReplacement, column, schema ) }
						rowId={ row_id }
						disabled={ isReplacing }
						schema={ getSchemaColumn( schema.columns, column.column_id ) }
						source={ schema.type }
						key={ column.column_id }
					/>
				) ) }

				<Actions result={ result } disabled={ isReplacing } />
			</td>
		</tr>
	);
}

function getGlobalReplacement( { action, actionOption, replacement } ) {
	if ( action === 'modify' ) {
		return actionOption;
	}

	if ( action === 'replace' ) {
		return [ {
			column: 'global',
			operation: 'replace',

			value: replacement,
		} ];
	}

	return [];
}

function mapStateToProps( state, ownProps ) {
	const { replacing, search, schema } = state.search;

	return {
		isReplacing: replacing.indexOf( ownProps.result.row_id ) !== -1,
		globalReplacement: getGlobalReplacement( search ),
		schema: getSchema( schema, ownProps.result.source_type ),
	};
}

export default connect(
	mapStateToProps,
	null
)( Result );
