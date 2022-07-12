/**
 * External dependencies
 */

import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { getPluginPage } from '../../lib/plugin';
import { Snackbar, Menu, ErrorBoundary, Error } from '@wp-plugin-components';
import { has_page_access } from '../../lib/capabilities';
import { clearErrors, clearNotices } from '../../state/message/action';
import DebugReport from './debug';
import ErrorDetails from './error-details';
import CrashHandler from './crash-handler';
import PageRouter from './page-router';
import PageContent from './page-content';
import { getErrorLinks, getErrorDetails } from '../../lib/error-links';
import CacheDetect from './cache-detect';
import DatabaseError from './database-error';
import UpdateNotice from './update-notice';
import './style.scss';

const getTitles = () => ( {
	search: __( 'Search Regex', 'search-regex' ),
	options: __( 'Options', 'search-regex' ),
	support: __( 'Support', 'search-regex' ),
	presets: __( 'Presets', 'search-regex' ),
} );

const getMenu = () =>
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
	].filter( ( option ) => has_page_access( option.value ) || ( option.value === '' && has_page_access( 'search' ) ) );

function Home( props ) {
	const { onClearErrors, errors, onClearNotices, notices } = props;
	const [ page, setPage ] = useState( getPluginPage() );

	if ( SEARCHREGEX_VERSION !== SearchRegexi10n.version ) {
		return <CacheDetect />;
	}

	return (
		<ErrorBoundary renderCrash={ CrashHandler } extra={ { page } }>
			<div className="wrap searchregex">
				<PageRouter page={ page } setPage={ setPage } onPageChange={ onClearErrors }>
					<h1 className="wp-heading-inline">{ getTitles()[ page ] }</h1>

					<UpdateNotice />

					<Menu
						onChangePage={ ( newPage ) => setPage( newPage === '' ? 'search' : newPage ) }
						menu={ getMenu() }
						home="search"
						currentPage={ page }
						urlBase={ SearchRegexi10n.pluginRoot }
					/>

					{ errors.length > 0 && errors[ 0 ].code === 'searchregex_database' ? (
						<DatabaseError error={ errors[ 0 ] } onClear={ onClearErrors } />
					) : (
						<Error
							errors={ errors }
							onClear={ onClearErrors }
							renderDebug={ DebugReport }
							details={ getErrorDetails() }
							links={ getErrorLinks() }
						>
							<ErrorDetails />
						</Error>
					) }

					<PageContent page={ page } />

					<Snackbar notices={ notices } onClear={ onClearNotices } />
				</PageRouter>
			</div>
		</ErrorBoundary>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onClearErrors: () => {
			dispatch( clearErrors() );
		},
		onClearNotices: () => {
			dispatch( clearNotices() );
		},
	};
}

function mapStateToProps( state ) {
	const {
		message: { errors, notices },
	} = state;

	return {
		errors,
		notices,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Home );
