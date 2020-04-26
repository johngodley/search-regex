/* global SEARCHREGEX_VERSION, SearchRegexi10n */
/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

import { getPluginPage } from 'lib/wordpress-url';
import Options from 'page/options';
import Support from 'page/support';
import SearchReplace from 'page/search-replace';
import Error from 'component/error';
import Notice from 'component/notice';
import Menu from 'component/menu';
import ExternalLink from 'component/external-link';
import { clearErrors } from 'state/message/action';
import './style.scss';

const getTitles = () => ( {
	search: __( 'Search Regex' ),
	options: __( 'Options' ),
	support: __( 'Support' ),
} );

class Home extends React.Component {
	constructor( props ) {
		super( props );

		this.state = {
			page: getPluginPage(),
			clicked: 0,
			stack: false,
			error: SEARCHREGEX_VERSION !== SearchRegexi10n.version,
			info: false,
		};

		window.addEventListener( 'popstate', this.onPageChanged );
	}

	componentDidCatch( error, info ) {
		this.setState( { error: true, stack: error, info } );
	}

	componentWillUnmount() {
		window.removeEventListener( 'popstate', this.onPageChanged );
	}

	onPageChanged = () => {
		const page = getPluginPage();

		this.changePage( page );
		this.setState( { page, clicked: this.state.clicked + 1 } );
	}

	onChangePage = ( page, url ) => {
		if ( page === '' ) {
			page = 'search';
		}

		this.props.onClear();

		history.pushState( {}, null, url );

		this.changePage( page );
		this.setState( {
			page,
			clicked: this.state.clicked + 1,
		} );
	}

	changePage( page ) {
	}

	getContent( page ) {
		const { clicked } = this.state;

		switch ( page ) {
			case 'support':
				return <Support />;

			case 'options':
				return <Options />;
		}

		return <SearchReplace />;
	}

	renderError() {
		const debug = [
			SearchRegexi10n.versions,
			'Buster: ' + SEARCHREGEX_VERSION + ' === ' + SearchRegexi10n.version,
			'',
			this.state.stack,
		];

		if ( this.state.info && this.state.info.componentStack ) {
			debug.push( this.state.info.componentStack );
		}

		if ( SEARCHREGEX_VERSION !== SearchRegexi10n.version ) {
			return (
				<div className="red-error">
					<h2>{ __( 'Cached Search Regex detected' ) }</h2>
					<p>{ __( 'Please clear your browser cache and reload this page.' ) }</p>
					<p>
						{ __( 'If you are using a caching system such as Cloudflare then please read this: ' ) }
						<ExternalLink url="https://searchregex.com/support/problems/cloudflare/">{ __( 'clearing your cache.' ) }</ExternalLink>
					</p>
					<p><textarea readOnly={ true } rows={ debug.length + 3 } cols="120" value={ debug.join( '\n' ) } spellCheck={ false }></textarea></p>
				</div>
			);
		}

		return (
			<div className="red-error">
				<h2>{ __( 'Something went wrong 🙁' ) }</h2>

				<p>
					{ __( 'Search Regex is not working. Try clearing your browser cache and reloading this page.' ) } &nbsp;
					{ __( 'If you are using a page caching plugin or service (CloudFlare, OVH, etc) then you can also try clearing that cache.' ) }
				</p>

				<p>
					{ __( "If that doesn't help, open your browser's error console and create a {{link}}new issue{{/link}} with the details.", {
						components: {
							link: <ExternalLink url="https://github.com/johngodley/searchregex/issues" />,
						},
					} ) }
				</p>
				<p>
					{ __( 'Please mention {{code}}%s{{/code}}, and explain what you were doing at the time', {
						components: {
							code: <code />,
						},
						args: this.state.page,
					} ) }
				</p>
				<p><textarea readOnly={ true } rows={ debug.length + 8 } cols="120" value={ debug.join( '\n' ) } spellCheck={ false }></textarea></p>
			</div>
		);
	}

	render() {
		const { error, page } = this.state;
		const title = getTitles()[ page ];

		if ( error ) {
			return this.renderError();
		}

		return (
			<div className="wrap searchregex">
				<h1 className="wp-heading-inline">{ title }</h1>

				<Menu onChangePage={ this.onChangePage } current={ page } />
				<Error />

				{ this.getContent( page ) }

				<Notice />
			</div>
		);
	}
}

function mapDispatchToProps( dispatch ) {
	return {
		onClear: () => {
			dispatch( clearErrors() );
		},
	};
}

function mapStateToProps( state ) {
	const { message: { errors } } = state;

	return {
		errors,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps,
)( Home );
