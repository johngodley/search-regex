import clsx from 'clsx';
import RestrictedMatches from '../result/column/restricted-matches';
import Replacement from './replacement';
import { useSaveRow } from '../../hooks/use-search';
import type { Match as MatchType, Schema } from '../../types/search';
import './style.scss';

interface SpecificReplacement {
	replaceValue: string;
	operation: string;
	source: string;
	[ key: string ]: unknown;
}

interface MatchProps {
	beforePhrase: JSX.Element;
	match: MatchType;
	onReplace: ( replaceValue: SpecificReplacement & { searchValue: string; posId: number } ) => void;
	column: string;
	schema: Schema;
	rowId: number;
}

interface CropValue {
	start?: number;
	end?: number;
}

interface HighlightMatchesProps {
	matches: MatchType[];
	count: number;
	source: string;
	column: string;
	schema: Schema;
	className?: string;
	rowId: number;
	crop?: CropValue;
}

/**
 * Display a matched phrase.
 * @param props
 */
function Match( props: MatchProps ): JSX.Element {
	const { beforePhrase, onReplace, column, schema, rowId } = props;
	const { match, pos_id: posId, captures, replacement } = props.match;

	return (
		<>
			{ beforePhrase }

			<Replacement
				onSave={ ( phrase ) =>
					onReplace( { ...phrase, searchValue: match, posId, source: schema.source || '' } )
				}
				match={ match }
				replacement={ replacement }
				captures={ captures }
				canReplace={ true }
				column={ column }
				schema={ schema }
				rowId={ rowId }
			/>
		</>
	);
}

/**
 * Highlight all matches in a context.
 * @param props
 */
function HighlightMatches( props: HighlightMatchesProps ): JSX.Element {
	const { matches, count, source, column, schema, className, rowId, crop = {} } = props;
	const saveRowMutation = useSaveRow();
	let offset = 0;

	const onReplace = ( replaceValue: SpecificReplacement & { searchValue: string; posId: number } ) => {
		saveRowMutation.mutate( { replacement: replaceValue, rowId: String( rowId ) } );
	};

	return (
		<div className={ clsx( 'searchregex-match__context', className ) }>
			{ matches.map( ( match, pos ) => {
				const oldOffset = offset;

				offset = match.context_offset + match.match.length;

				return (
					<Match
						match={ match }
						key={ match.pos_id }
						onReplace={ onReplace }
						beforePhrase={
							<>
								{ crop.start && crop.start > 0 && pos === 0 && <>&hellip; </> }
								{ source.substring( oldOffset, match.context_offset ) }
							</>
						}
						column={ column }
						schema={ schema }
						rowId={ rowId }
					/>
				);
			} ) }

			{ source.substring( offset ) }
			{ crop.end && crop.end > 0 && <> &hellip;</> }

			{ matches.length !== count && <RestrictedMatches /> }
		</div>
	);
}

export default HighlightMatches;
