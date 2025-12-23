import clsx from 'clsx';
import ResultColumn from './column';
import ResultTitle from './result-title';
import Actions from './actions';
import { getSchema, getSchemaColumn } from '../../lib/search-utils';
import { getReplacedColumn } from './modify-column';
import { useSearchStore } from '../../stores/search-store';
import './style.scss';
import type { ResultColumn as ResultColumnType, Schema, SchemaColumn, Result as ResultType } from '../../types/search';

interface GlobalReplacementItem {
	column: string;
	operation?: string;
	value?: string;
}

interface ResultProps {
	result: ResultType;
}

function getReplacement(
	globalReplacement: GlobalReplacementItem[],
	column: ResultColumnType,
	schema: Schema | undefined
): ResultColumnType {
	if ( ! schema ) {
		return column;
	}

	if ( globalReplacement && globalReplacement.length > 0 && globalReplacement[ 0 ]?.column === 'global' ) {
		const globalColumn = schema?.columns.find( ( item ) => item.column === column.column_id );

		if ( globalColumn && ( globalColumn as any ).global ) {
			const schemaColumn = schema?.columns.find( ( item ) => item.column === column.column_id );
			if ( schemaColumn ) {
				return getReplacedColumn( column, globalReplacement[ 0 ] as any, schemaColumn as SchemaColumn );
			}
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
	const { result } = props;

	const replacing = useSearchStore( ( state ) => state.replacing );
	const search = useSearchStore( ( state ) => state.search );
	const schemaList = useSearchStore( ( state ) => state.schema );
	const { columns, actions, row_id: rowId, source_name: sourceName, source_type: sourceType, title } = result;

	const isReplacing = replacing.indexOf( rowId ) !== -1;
	const globalReplacement = getGlobalReplacement( search as any );
	const schema = getSchema( schemaList, sourceType );

	if ( ! schema ) {
		return (
			<tr className={ clsx( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
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
		<tr className={ clsx( 'searchregex-result', { 'searchregex-result__updating': isReplacing } ) }>
			<td className="searchregex-result__table">
				<span title={ sourceType }>{ sourceName }</span>
			</td>
			<td className="searchregex-result__row">
				{ new Intl.NumberFormat( SearchRegexi10n.locale ).format( parseInt( rowId, 10 ) ) }
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

export { Result };
export default Result;
