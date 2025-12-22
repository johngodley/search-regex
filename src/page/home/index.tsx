import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { getPluginPage } from '../../lib/plugin';
import { Snackbar, Menu, ErrorBoundary, Error } from '@wp-plugin-components';
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

		const defaultPresetId = SearchRegexi10n.settings?.defaultPreset;
		if ( defaultPresetId !== undefined ) {
			const presetId = String( defaultPresetId );
			const preset = presets.find( ( item: PresetValue ) => item.id === presetId );
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
					<h1 className="wp-heading-inline">{ getTitles()[ page ] }</h1>

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
