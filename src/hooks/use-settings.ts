import { __ } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiFetch, getApiRequest, postApiRequest } from '@wp-plugin-lib';
import { useMessageStore } from '../stores/message-store';
import { settingsResponseSchema, type SettingsValues, type SettingsResponse } from '../lib/api-schemas';

const SETTINGS_QUERY_KEY = [ 'settings' ];

export function useSettings() {
	const initialSettings = SearchRegexi10n.settings;
	return useQuery< SettingsValues >( {
		queryKey: SETTINGS_QUERY_KEY,
		queryFn: async () => {
			const response = await apiFetch( getApiRequest( 'search-regex/v1/setting' ) );

			const validated = settingsResponseSchema.parse( response );
			return validated.settings;
		},
		...( initialSettings ? { initialData: initialSettings } : {} ),
		staleTime: Infinity,
	} );
}

export function useSaveSettings() {
	const queryClient = useQueryClient();
	const addNotice = useMessageStore( ( state ) => state.addNotice );
	const addError = useMessageStore( ( state ) => state.addError );

	return useMutation< SettingsResponse, Error, Partial< SettingsValues > >( {
		mutationFn: async ( settings ) => {
			const response = await apiFetch( postApiRequest( 'search-regex/v1/setting', settings ) );

			return settingsResponseSchema.parse( response );
		},
		onSuccess: ( data ) => {
			queryClient.setQueryData( SETTINGS_QUERY_KEY, data.settings );
			addNotice( __( 'Settings saved', 'search-regex' ) );
		},
		onError: ( error ) => {
			addError( error );
		},
	} );
}
