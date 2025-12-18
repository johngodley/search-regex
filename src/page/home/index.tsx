import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';
import { Dispatch } from 'redux';
import { getPluginPage } from '../../lib/plugin';
import { Snackbar, Menu, ErrorBoundary, Error } from '@wp-plugin-components';
/* eslint-disable camelcase */
import { has_page_access } from '../../lib/capabilities';
/* eslint-enable camelcase */
import { clearErrors, clearNotices } from '../../state/message/action';
import { setPreset } from '../../state/preset/action';
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

interface RootState {
	message: {
		errors: ErrorMessage[];
		notices: string[];
	};
	preset: {
		presets: PresetValue[];
	};
}

interface HomeProps {
	onClearErrors: () => void;
	onResetPreset: ( preset: PresetValue | null ) => void;
	errors: ErrorMessage[];
	onClearNotices: () => void;
	notices: string[];
	presets: PresetValue[];
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

function Home( props: HomeProps ) {
	const { onClearErrors, onResetPreset, errors, onClearNotices, notices, presets } = props;
	const [ page, setPage ] = useState( getPluginPage() );

	if ( SEARCHREGEX_VERSION !== SearchRegexi10n.version ) {
		return <CacheDetect />;
	}

	function pageChange() {
		onClearErrors();

		if (
			SearchRegexi10n.settings.defaultPreset &&
			presets.find( ( item ) => item.id === SearchRegexi10n.settings.defaultPreset )
		) {
			onResetPreset( presets.find( ( item ) => item.id === SearchRegexi10n.settings.defaultPreset ) || null );
		} else {
			onResetPreset( null );
		}
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

					{ errors.length > 0 && errors[ 0 ].code === 'searchregex_database' ? (
						<DatabaseError
							error={ errors[ 0 ] as { jsonData: string; code: string } }
							onClear={ onClearErrors }
						/>
					) : (
						<Error
							errors={ errors }
							onClear={ onClearErrors }
							renderDebug={ DebugReport }
							details={ getErrorDetails() }
							links={ getErrorLinks() }
							locale={ SearchRegexi10n.locale }
						>
							<ErrorDetails />
						</Error>
					) }

					<PageContent page={ page } />

					<Snackbar notices={ notices } onClear={ onClearNotices } snackBarViewText={ __( 'View' ) } />
				</PageRouter>
			</div>
		</ErrorBoundary>
	);
}

function mapDispatchToProps( dispatch: Dispatch ) {
	return {
		onClearErrors: () => {
			dispatch( clearErrors() );
		},
		onClearNotices: () => {
			dispatch( clearNotices() );
		},
		onResetPreset: ( preset: PresetValue | null ) => {
			dispatch( setPreset( preset ) );
		},
	};
}

function mapStateToProps( state: RootState ) {
	const {
		message: { errors, notices },
	} = state;
	const { presets } = state.preset;

	return {
		errors,
		notices,
		presets,
	};
}

export default connect( mapStateToProps, mapDispatchToProps )( Home );
