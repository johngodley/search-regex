import { useMutation } from '@tanstack/react-query';
import { apiFetch, postApiRequest } from '@wp-plugin-lib';
import { ApiUtils } from '../lib/api-utils';
import type { SearchValues, Result } from '../types/search';
import { useMessageStore } from '../stores/message-store';
import { useSearchStore } from '../stores/search-store';
import {
	searchResponseSchema,
	exportActionResponseSchema,
	deleteRowResponseSchema,
	loadRowResponseSchema,
	saveRowResponseSchema,
	sourceCompleteResponseSchema,
	type SearchResponse,
	type DeleteRowResponse,
	type LoadRowResponse,
	type SaveRowResponse,
	type SourceCompleteResponse,
} from '../lib/api-schemas';

interface SearchParams extends SearchValues {
	page?: number;
	save?: boolean;
	searchDirection?: string;
	limit?: number;
}

export function useSearch() {
	const addError = useMessageStore( ( state ) => state.addError );
	const appendExportData = useSearchStore( ( state ) => state.appendExportData );

	return useMutation< SearchResponse, Error, SearchParams >( {
		mutationFn: async ( searchParams ) => {
			const response = await apiFetch( postApiRequest( 'search-regex/v1/search', searchParams ) );
			// For export saves, parse with the export schema and accumulate the raw data,
			// then return a SearchResponse-shaped object with empty results so all callers
			// can treat the response uniformly.
			if ( searchParams.action === 'export' && searchParams.save ) {
				const parsed = exportActionResponseSchema.parse( response );
				appendExportData( parsed.results );
				return { ...parsed, results: [] };
			}
			return searchResponseSchema.parse( response );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useDeleteRow() {
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< DeleteRowResponse, Error, { source: string; rowId: string | number } >( {
		mutationFn: async ( { source, rowId } ) => {
			const response = await apiFetch(
				postApiRequest( `search-regex/v1/source/${ source }/row/${ rowId }/delete` )
			);
			return deleteRowResponseSchema.parse( response );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useLoadRow() {
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< LoadRowResponse, Error, { source: string; rowId: string | number } >( {
		mutationFn: async ( { source, rowId } ) => {
			const response = await apiFetch( ApiUtils.source.loadRow( source, rowId ) );
			return loadRowResponseSchema.parse( response );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useSaveRow() {
	const addError = useMessageStore( ( state ) => state.addError );
	const search = useSearchStore( ( state ) => state.search );
	const setResults = useSearchStore( ( state ) => state.setResults );

	return useMutation<
		SaveRowResponse,
		Error,
		{ replacement: any; rowId: string },
		{ previousResults: Result[]; rowId: string }
	>( {
		mutationFn: async ( { replacement, rowId } ) => {
			const response = await apiFetch(
				postApiRequest( `search-regex/v1/source/${ replacement.source }/row/${ rowId }`, {
					...search,
					replacement,
				} )
			);
			return saveRowResponseSchema.parse( response );
		},
		onMutate: async ( { rowId } ) => {
			// Save the current results for rollback
			const currentResults = useSearchStore.getState().results;
			const previousResults = [ ...currentResults ];

			return { previousResults, rowId: String( rowId ) };
		},
		onSuccess: ( data, { rowId } ) => {
			// Update with the actual response data from the API
			const currentResults = useSearchStore.getState().results;
			const rowIdStr = String( rowId );
			const { result: savedResult } = data;

			// A `null` result means the replacement removed the row's last match against the
			// current search, so it no longer belongs in the result set - drop it instead of
			// trying to update it.
			if ( savedResult === null ) {
				setResults( currentResults.filter( ( result ) => String( result.row_id ) !== rowIdStr ) );
				return;
			}

			const updatedResults = currentResults.map( ( result ) => {
				// Match by string comparison since Result.row_id is a string
				if ( String( result.row_id ) === rowIdStr ) {
					// Convert API response (number row_id) to match Result interface (string row_id)
					const updatedResult = {
						...savedResult,
						row_id: String( savedResult.row_id ),
					};
					return updatedResult as Result;
				}
				return result;
			} );

			setResults( updatedResults );
		},
		onError: ( error, _variables, context ) => {
			// Rollback to previous results on error
			if ( context?.previousResults ) {
				setResults( context.previousResults );
			}
			addError( error );
		},
	} );
}

export function useSourceComplete() {
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< SourceCompleteResponse, Error, { source: string; column: string; value: string } >( {
		mutationFn: async ( { source, column, value } ) => {
			const response = await apiFetch( ApiUtils.source.complete( source, column, value ) );
			return sourceCompleteResponseSchema.parse( response );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}
