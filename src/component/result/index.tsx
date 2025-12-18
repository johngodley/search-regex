import classnames from 'classnames';
import { connect } from 'react-redux';
import ResultColumn from './column';
import ResultTitle from './result-title';
import Actions from './actions';
import { getSchema, getSchemaColumn } from '../../state/search/selector';
import { getReplacedColumn } from './modify-column';
import './style.scss';
import type { ResultColumn as ResultColumnType, Schema, SchemaColumn, Result } from '../../types/search';
import type { RootState } from '../../state/reducers';

interface GlobalReplacementItem {
	column: string;
	operation?: string;
	value?: string;
}

interface ResultOwnProps {
	result: Result;
}

interface ResultStateProps {
	isReplacing: boolean;
	globalReplacement: GlobalReplacementItem[];
	schema: Schema | undefined;
}

type ResultProps = ResultOwnProps & ResultStateProps;

function getReplacement(
	globalReplacement: GlobalReplacementItem[],
	column: ResultColumnType,
	schema: Schema | undefined
): ResultColumnType {
	if ( ! schema ) {
		return column;
	}

	if ( globalReplacement && globalReplacement.length > 0 && globalReplacement[ 0 ].column === 'global' ) {
		const globalColumn = schema.columns.find( ( item ) => item.column === column.column_id );

		if ( globalColumn && ( globalColumn as any ).global ) {
			const schemaColumn = schema.columns.find( ( item ) => item.column === column.column_id );
			return getReplacedColumn( column, globalReplacement[ 0 ] as any, schemaColumn as SchemaColumn );
		}
	}

	const globalColumn = globalReplacement.find( ( item ) => item.column === column.column_id );
	if ( globalColumn ) {
		const schemaColumn = schema.columns.find( ( item ) => item.column === column.column_id );
		return getReplacedColumn( column, globalColumn as any, schemaColumn as SchemaColumn );
	}

	return column;
}

function Result( props: ResultProps ): JSX.Element {
	const { result, globalReplacement, isReplacing, schema } = props;
	const { columns, actions, row_id: rowId, source_name: sourceName, source_type: sourceType, title } = result;

	if ( ! schema ) {
		return (
			<tr className={ classnames( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
				<td className="searchregex-result__table">
					<span title={ sourceType }>{ sourceName }</span>
				</td>
				<td className="searchregex-result__row">{ rowId }</td>
				<td className="searchregex-result__match">
					<h2>{ title }</h2>
					<p>Schema not found for { sourceType }</p>
				</td>
			</tr>
		);
	}

	return (
		<tr className={ classnames( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
			<td className="searchregex-result__table">
				<span title={ sourceType }>{ sourceName }</span>
			</td>
			<td className="searchregex-result__row">
				{ new Intl.NumberFormat( ( window as any ).SearchRegexi10n.locale as string ).format(
					parseInt( rowId, 10 )
				) }
			</td>

			<td className="searchregex-result__match">
				<h2>
					<ResultTitle view={ ( actions as any ).view } title={ title } />
				</h2>

				{ columns.map( ( column ) => {
					const schemaColumn = getSchemaColumn( schema.columns, column.column_id ) || {
						column: column.column_id,
						title: column.column_id,
						type: 'string' as const,
					};

					return (
						<ResultColumn
							column={ getReplacement( globalReplacement, column, schema ) }
							rowId={ rowId }
							disabled={ isReplacing }
							schema={ schemaColumn as SchemaColumn }
							source={ schema.type }
							key={ column.column_id }
						/>
					);
				} ) }

				<Actions result={ result } disabled={ isReplacing } />
			</td>
		</tr>
	);
}

interface SearchState {
	action: string;
	actionOption: GlobalReplacementItem[];
	replacement: string;
}

function getGlobalReplacement( { action, actionOption, replacement }: SearchState ): GlobalReplacementItem[] {
	if ( action === 'modify' ) {
		return actionOption;
	}

	if ( action === 'replace' ) {
		return [
			{
				column: 'global',
				operation: 'replace',
				value: replacement,
			},
		];
	}

	return [];
}

function mapStateToProps( state: RootState, ownProps: ResultOwnProps ): ResultStateProps {
	const { replacing, search, schema } = state.search;

	return {
		isReplacing: replacing.indexOf( ownProps.result.row_id ) !== -1,
		globalReplacement: getGlobalReplacement( search as any ),
		schema: getSchema( schema, ownProps.result.source_type ),
	};
}

export default connect< ResultStateProps, Record< string, never >, ResultOwnProps, RootState >(
	mapStateToProps,
	null
)( Result );
