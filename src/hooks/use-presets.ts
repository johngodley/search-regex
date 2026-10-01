import { __ } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiFetch, getApiRequest, postApiRequest, uploadApiRequest } from '@wp-plugin-lib';
import getPreload from '../lib/preload';
import { useMessageStore } from '../stores/message-store';
import type { PresetValue } from '../types/preset';
import type { SearchValues } from '../types/search';
import {
	presetResponseSchema,
	presetUploadResponseSchema,
	type PresetResponse,
	type PresetUploadResponse,
} from '../lib/api-schemas';

export const PRESETS_QUERY_KEY = [ 'presets' ];

export function usePresets() {
	return useQuery< PresetValue[] >( {
		queryKey: PRESETS_QUERY_KEY,
		queryFn: async () => {
			return getPreload< PresetValue[] >( 'presets', [] );
		},
		initialData: getPreload< PresetValue[] >( 'presets', [] ),
		staleTime: Infinity,
	} );
}

export function useSavePreset() {
	const queryClient = useQueryClient();
	const addNotice = useMessageStore( ( state ) => state.addNotice );
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< PresetResponse, Error, { name: string; searchValues: SearchValues } >( {
		mutationFn: async ( { name, searchValues } ) => {
			const response = await apiFetch( postApiRequest( 'search-regex/v1/preset', { ...searchValues, name } ) );
			return presetResponseSchema.parse( response );
		},
		onSuccess: ( data ) => {
			queryClient.setQueryData( PRESETS_QUERY_KEY, data.presets );
			addNotice( __( 'Preset saved', 'search-regex' ) );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useUpdatePreset() {
	const queryClient = useQueryClient();
	const addNotice = useMessageStore( ( state ) => state.addNotice );
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< PresetResponse, Error, PresetValue >( {
		mutationFn: async ( preset ) => {
			const response = await apiFetch(
				postApiRequest(
					`search-regex/v1/preset/id/${ preset.id }`,
					preset as unknown as Record< string, unknown >
				)
			);
			return presetResponseSchema.parse( response );
		},
		onSuccess: ( data ) => {
			queryClient.setQueryData( PRESETS_QUERY_KEY, data.presets );
			addNotice( __( 'Preset saved', 'search-regex' ) );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useDeletePreset() {
	const queryClient = useQueryClient();
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< PresetResponse, Error, string >( {
		mutationFn: async ( id ) => {
			const response = await apiFetch( postApiRequest( `search-regex/v1/preset/id/${ id }/delete` ) );
			return presetResponseSchema.parse( response );
		},
		onSuccess: ( data ) => {
			queryClient.setQueryData( PRESETS_QUERY_KEY, data.presets );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useUploadPreset() {
	const queryClient = useQueryClient();
	const addNotice = useMessageStore( ( state ) => state.addNotice );
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< PresetUploadResponse, Error, File >( {
		mutationFn: async ( file ) => {
			const response = await apiFetch( uploadApiRequest( 'search-regex/v1/preset/import', {}, file ) );
			return presetUploadResponseSchema.parse( response );
		},
		onSuccess: ( data ) => {
			queryClient.setQueryData( PRESETS_QUERY_KEY, data.presets );
			addNotice( __( 'Preset uploaded', 'search-regex' ) );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}

export function useExportPresets() {
	return {
		exportPresets: () => {
			const request = getApiRequest( 'search-regex/v1/preset', { force: true } );
			document.location.href = apiFetch.getUrl( request.url ) + '&_wpnonce=' + SearchRegexi10n.api.WP_API_nonce;
		},
	};
}
