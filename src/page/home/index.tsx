import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { getPluginPage } from '../../lib/plugin';
import { Snackbar, Menu, ErrorBoundary, Error, Select } from '@wp-plugin-components';
/* eslint-disable camelcase */
import { has_page_access } from '../../lib/capabilities';
/* eslint-enable camelcase */
import { useMessageStore } from '../../stores/message-store';
import { usePresets } from '../../hooks/use-presets';
import { usePresetStore } from '../../stores/preset-store';
import DebugReport from './debug';
import ErrorDetails from './error-details';
import CrashHandler from './crash-handler';
import PageRouter from './page-router';
import PageContent from './page-content';
import { getErrorLinks, getErrorDetails } from '../../lib/error-links';
import CacheDetect from './cache-detect';
import DatabaseError from './database-error';
import UpdateNotice from './update-notice';
import type { PresetValue } from '../../types/preset';
import type { SettingsValues } from '../../lib/api-schemas';
import { useSearchStore } from '../../stores/search-store';
import './style.scss';

interface MenuItem {
	name: string;
	value: string;
}

interface ErrorMessage {
	code: string;
	jsonData?: string;
}

const getTitles = (): Record< string, string > => ( {
	search: __( 'Search Regex', 'search-regex' ),
	options: __( 'Options', 'search-regex' ),
	support: __( 'Support', 'search-regex' ),
	presets: __( 'Presets', 'search-regex' ),
} );

const getMenu = (): MenuItem[] =>
	[
		{
			name: __( 'Search & Replace', 'search-regex' ),
			value: '',
		},
		{
			name: __( 'Presets', 'search-regex' ),
			value: 'presets',
		},
		{
			name: __( 'Options', 'search-regex' ),
			value: 'options',
		},
		{
			name: __( 'Support', 'search-regex' ),
			value: 'support',
		},
		/* eslint-disable camelcase */
	].filter( ( option ) => has_page_access( option.value ) || ( option.value === '' && has_page_access( 'search' ) ) );
/* eslint-enable camelcase */

function Home() {
	const [ page, setPage ] = useState( getPluginPage() );

	const mode = useSearchStore( ( state ) => state.mode );
	const setMode = useSearchStore( ( state ) => state.setMode );

	const errors = useMessageStore( ( state ) => state.errors );
	const notices = useMessageStore( ( state ) => state.notices );
	const clearErrors = useMessageStore( ( state ) => state.clearErrors );
	const clearNotices = useMessageStore( ( state ) => state.clearNotices );
	const setCurrentPreset = usePresetStore( ( state ) => state.setCurrentPreset );

	const { data: presets = [] } = usePresets();

	if ( SEARCHREGEX_VERSION !== SearchRegexi10n.version ) {
		return <CacheDetect />;
	}

	function pageChange() {
		clearErrors();

		const settings = SearchRegexi10n.settings;

		let startupMode: SettingsValues[ 'startupMode' ] | undefined = settings?.startupMode;
		let startupPresetId: string | undefined = settings?.startupPreset;

		// Backwards compatibility: fall back to legacy defaultPreset if
		// startupMode has not been initialised yet.
		if ( ! startupMode && settings && ( settings as any ).defaultPreset !== undefined ) {
			const legacyDefault = ( settings as any ).defaultPreset;
			if ( legacyDefault ) {
				startupMode = 'preset';
				startupPresetId = String( legacyDefault );
			}
		}

		if ( startupMode === 'preset' && startupPresetId ) {
			const preset = presets.find( ( item: PresetValue ) => item.id === startupPresetId );
			if ( preset ) {
				setCurrentPreset( preset );
				return;
			}
		}

		setCurrentPreset( null );
	}

	return (
		<ErrorBoundary renderCrash={ CrashHandler } extra={ { page } }>
			<div className="wrap searchregex">
				<PageRouter
					page={ page }
					setPage={ ( newPage: string ) => setPage( newPage as typeof page ) }
					onPageChange={ pageChange }
				>
					<div className="searchregex-header">
						<h1 className="wp-heading-inline">{ getTitles()[ page ] }</h1>
						{ page === 'search' && (
							<Select
								items={ [
									{ value: 'simple', label: __( 'Simple mode', 'search-regex' ) },
									{ value: 'advanced', label: __( 'Advanced mode', 'search-regex' ) },
								] }
								name="search-mode"
								value={ mode }
								onChange={ ( ev: React.ChangeEvent< HTMLSelectElement > ) =>
									setMode( ev.target.value as 'simple' | 'advanced' )
								}
							/>
						) }
					</div>

					<UpdateNotice />

					<Menu
						onChangePage={ ( newPage ) =>
							setPage( ( newPage === '' ? 'search' : newPage ) as typeof page )
						}
						menu={ getMenu() }
						home="search"
						currentPage={ page }
						urlBase={ SearchRegexi10n.pluginRoot }
					/>

					{ errors.length > 0 && ( errors[ 0 ] as ErrorMessage ).code === 'searchregex_database' ? (
						<DatabaseError
							error={ errors[ 0 ] as { jsonData: string; code: string } }
							onClear={ clearErrors }
						/>
					) : (
						<Error
							errors={ errors }
							onClear={ clearErrors }
							renderDebug={ DebugReport }
							details={ getErrorDetails() }
							links={ getErrorLinks() }
							locale={ SearchRegexi10n.locale }
						>
							<ErrorDetails />
						</Error>
					) }

					<PageContent page={ page } />

					<Snackbar notices={ notices } onClear={ clearNotices } snackBarViewText={ __( 'View' ) } />
				</PageRouter>
			</div>
		</ErrorBoundary>
	);
}

export default Home;
