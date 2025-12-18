import { Component, type MouseEvent } from 'react';
import { connect } from 'react-redux';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import type { ThunkDispatch } from 'redux-thunk';
import ApiResultComponent from './api-result';
import { Spinner } from '@wp-plugin-components';
import { getRestApi } from '../../page/options/options-form';
import { checkApi } from '../../state/settings/action';
import './style.scss';

const STATUS_OK = 'ok';
const STATUS_FAIL = 'fail';
const STATUS_LOADING = 'loading';
const STATUS_WARNING_CURRENT = 'warning-current';
const STATUS_WARNING = 'warning-not-selected';

interface ApiTestResult {
	status: string;
}

interface ApiResult {
	GET?: ApiTestResult;
	POST?: ApiTestResult;
}

interface ApiTestResults {
	[ key: string ]: ApiResult;
}

interface RestApiStatusOwnProps {
	apiTest: ApiTestResults;
	routes: Record< string, string >;
	current: string;
	allowChange?: boolean;
}

interface RestApiStatusDispatchProps {
	onCheckApi: ( api: Array< { id: string; url: string } > ) => void;
}

type RestApiStatusProps = RestApiStatusOwnProps & RestApiStatusDispatchProps;

interface RestApiStatusState {
	showing: boolean;
}

interface RootState {
	settings: {
		api: {
			routes: Record< string, string >;
			current: string;
		};
		apiTest: ApiTestResults;
	};
}

const getApiResult = ( results: ApiTestResults, name: string ): ApiResult =>
	results && results[ name ] ? results[ name ] : {};
const isError = ( result: ApiResult ): boolean =>
	result.GET && result.POST && ( result.GET.status === STATUS_FAIL || result.POST.status === STATUS_FAIL )
		? true
		: false;
const isWorking = ( result: ApiResult ): boolean =>
	result.GET && result.POST && result.GET.status === STATUS_OK && result.POST.status === STATUS_OK ? true : false;

class RestApiStatus extends Component< RestApiStatusProps, RestApiStatusState > {
	constructor( props: RestApiStatusProps ) {
		super( props );

		this.state = { showing: false };
	}

	componentDidMount(): void {
		this.onTry();
	}

	onTry(): void {
		const { routes } = this.props;
		const untested = Object.keys( routes ).map( ( id ) => ( { id, url: routes[ id ] } ) );

		this.props.onCheckApi( untested.filter( ( item ) => item ) );
	}

	onRetry = ( ev: MouseEvent< HTMLButtonElement > ): void => {
		ev.preventDefault();
		this.setState( { showing: false } );
		this.onTry();
	};

	getPercent( apiTest: ApiTestResults, routes: any[] ): number {
		if ( Object.keys( apiTest ).length === 0 ) {
			return 0;
		}

		const total = routes.length * 2;
		let finished = 0;

		for ( let index = 0; index < Object.keys( apiTest ).length; index++ ) {
			const key = Object.keys( apiTest )[ index ];

			if ( apiTest[ key ] && apiTest[ key ].GET && apiTest[ key ].GET.status !== STATUS_LOADING ) {
				finished++;
			}

			if ( apiTest[ key ] && apiTest[ key ].POST && apiTest[ key ].POST.status !== STATUS_LOADING ) {
				finished++;
			}
		}

		return Math.round( ( finished / total ) * 100 );
	}

	getApiStatus( results: ApiTestResults, routes: any[], current: string ): string {
		const failed = Object.keys( results ).filter( ( key ) => isError( results[ key ] ) ).length;

		if ( failed === 0 ) {
			return 'ok';
		} else if ( failed < routes.length ) {
			return isWorking( results[ current ] ) ? STATUS_WARNING_CURRENT : STATUS_WARNING;
		}

		return 'fail';
	}

	getApiStatusText( status: string ): string {
		if ( status === STATUS_OK ) {
			return __( 'Good', 'search-regex' );
		} else if ( status === STATUS_WARNING_CURRENT ) {
			return __( 'Working but some issues', 'search-regex' );
		} else if ( status === STATUS_WARNING ) {
			return __( 'Not working but fixable', 'search-regex' );
		}

		return __( 'Unavailable', 'search-regex' );
	}

	onShow = (): void => {
		this.setState( { showing: true } );
	};

	canShowProblem( status: string ): boolean {
		const { showing } = this.state;

		return showing || status === STATUS_FAIL || status === STATUS_WARNING;
	}

	renderError( status: string ): JSX.Element {
		const showing = this.canShowProblem( status );
		let message: string = __(
			'There are some problems connecting to your REST API. It is not necessary to fix these problems and the plugin is able to work.',
			'search-regex'
		);

		if ( status === STATUS_FAIL ) {
			message = __(
				'Your REST API is not working and the plugin will not be able to continue until this is fixed.',
				'search-regex'
			);
		} else if ( status === STATUS_WARNING ) {
			message = __(
				'You are using a broken REST API route. Changing to a working API should fix the problem.',
				'search-regex'
			);
		}

		return (
			<div className="api-result-log">
				<p>
					<strong>{ __( 'Summary', 'search-regex' ) }</strong>: { message }
				</p>

				{ ! showing && (
					<p>
						<button className="button-secondary" type="button" onClick={ this.onShow }>
							{ __( 'Show Problems', 'search-regex' ) }
						</button>
					</p>
				) }
			</div>
		);
	}

	render(): JSX.Element {
		const routeNames = getRestApi();
		const { apiTest, routes, current, allowChange } = this.props;
		const { showing } = this.state;
		const percent = this.getPercent( apiTest, routeNames );
		const status = this.getApiStatus( apiTest, routeNames, current );
		const showProblem = ( percent >= 100 && this.canShowProblem( status ) ) || showing;
		const statusClass = classnames( {
			'api-result-status': true,
			'api-result-status_good': status === STATUS_OK && percent >= 100,
			'api-result-status_problem': status === STATUS_WARNING_CURRENT && percent >= 100,
			'api-result-status_failed': ( status === STATUS_FAIL || status === STATUS_WARNING ) && percent >= 100,
		} );

		return (
			<div className="api-result-wrapper">
				<div className="api-result-header">
					<strong>REST API:</strong>

					<div className="api-result-progress">
						<span className={ statusClass }>
							{
								/* translators: %s: test percent */
								percent < 100 && sprintf( __( 'Testing - %s%%', 'search-regex' ), String( percent ) )
							}
							{ percent >= 100 && this.getApiStatusText( status ) }
						</span>

						{ percent < 100 && <Spinner /> }
					</div>

					{ percent >= 100 && status !== STATUS_OK && (
						<button className="button button-secondary api-result-retry" onClick={ this.onRetry }>
							{ __( 'Check Again', 'search-regex' ) }
						</button>
					) }
				</div>

				{ percent >= 100 && status !== STATUS_OK && this.renderError( status ) }

				{ showProblem &&
					routeNames.map( ( item, pos ) => {
						const apiItem = { value: String( item.value ), text: item.label };
						const result = getApiResult( apiTest, String( item.value ) );
						if ( ! result.GET || ! result.POST ) {
							return null;
						}
						return (
							<ApiResultComponent
								item={ apiItem }
								result={ result as any }
								routes={ routes }
								key={ pos }
								isCurrent={ current === String( item.value ) }
								allowChange={ allowChange || false }
							/>
						);
					} ) }
			</div>
		);
	}
}

function mapDispatchToProps( dispatch: ThunkDispatch< RootState, unknown, any > ): RestApiStatusDispatchProps {
	return {
		onCheckApi: ( api ) => {
			void dispatch( checkApi( api ) );
		},
	};
}

function mapStateToProps( state: RootState ): RestApiStatusOwnProps {
	const {
		api: { routes, current },
		apiTest,
	} = state.settings;

	return {
		apiTest,
		routes,
		current,
	};
}

export default connect< RestApiStatusOwnProps, RestApiStatusDispatchProps, Record< string, never >, RootState >(
	mapStateToProps,
	mapDispatchToProps
)( RestApiStatus );
