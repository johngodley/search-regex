import { useMutation } from '@tanstack/react-query';
import { apiFetch } from '@wp-plugin-lib';
import SearchRegexApi from '../lib/api-request';
import type { SearchValues, Result } from '../types/search';
import { useMessageStore } from '../stores/message-store';
import { useSearchStore } from '../stores/search-store';
import {
	searchResponseSchema,
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

	return useMutation< SearchResponse, Error, SearchParams >( {
		mutationFn: async ( searchParams ) => {
			const response = await apiFetch( SearchRegexApi.search.perform( searchParams ) );
			// Validate and parse response with Zod
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
			const response = await apiFetch( SearchRegexApi.source.deleteRow( source, rowId ) );
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
			const response = await apiFetch( SearchRegexApi.source.loadRow( source, rowId ) );
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
				SearchRegexApi.source.saveRow( replacement.source, rowId, replacement, search )
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

			const updatedResults = currentResults.map( ( result ) => {
				// Match by string comparison since Result.row_id is a string
				if ( String( result.row_id ) === rowIdStr ) {
					// Convert API response (number row_id) to match Result interface (string row_id)
					const updatedResult = {
						...data.result,
						row_id: String( data.result.row_id ),
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
			const response = await apiFetch( SearchRegexApi.source.complete( source, column, value ) );
			return sourceCompleteResponseSchema.parse( response );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}
