import { useState, useEffect, type MouseEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { apiFetch } from '@wp-plugin-lib';
import ApiResultComponent from './api-result';
import { Spinner } from '@wp-plugin-components';
import { getRestApi } from '../../page/options';
import { ApiUtils } from '../../lib/api-utils';
import { useSettings } from '../../hooks/use-settings';
import type { SettingsValues } from '../../lib/api-schemas';
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

interface RestApiStatusProps {
	allowChange?: boolean;
}

const getApiResult = ( results: ApiTestResults, name: string ): ApiResult =>
	results && results[ name ] ? results[ name ] : {};
const isError = ( result: ApiResult ): boolean =>
	result.GET && result.POST && ( result.GET.status === STATUS_FAIL || result.POST.status === STATUS_FAIL )
		? true
		: false;
const isWorking = ( result: ApiResult ): boolean =>
	result.GET && result.POST && result.GET.status === STATUS_OK && result.POST.status === STATUS_OK ? true : false;

function RestApiStatus( { allowChange = false }: RestApiStatusProps ) {
	const { data: settings } = useSettings() as { data: SettingsValues | undefined };
	const [ apiTest, setApiTest ] = useState< ApiTestResults >( {} );
	const [ showing, setShowing ] = useState( false );

	const routes = SearchRegexi10n.api.routes;
	const restApi = settings?.rest_api;
	const current = String( restApi !== undefined ? restApi : 0 );

	const checkApi = ( apiEndpoints: Array< { id: string; url: string } > ) => {
		for ( let index = 0; index < apiEndpoints.length; index++ ) {
			const endpoint = apiEndpoints[ index ];
			if ( ! endpoint ) {
				continue;
			}
			const { id, url } = endpoint;

			// Set loading state
			setApiTest( ( prev ) => ( {
				...prev,
				[ id ]: {
					GET: { status: STATUS_LOADING },
					POST: { status: STATUS_LOADING },
				},
			} ) );

			// Test GET
			setTimeout( () => {
				apiFetch( ApiUtils.plugin.checkApi( url ) )
					.then( () => {
						setApiTest( ( prev ) => ( {
							...prev,
							[ id ]: {
								...prev[ id ],
								GET: { status: STATUS_OK },
							},
						} ) );
					} )
					.catch( () => {
						setApiTest( ( prev ) => ( {
							...prev,
							[ id ]: {
								...prev[ id ],
								GET: { status: STATUS_FAIL },
							},
						} ) );
					} );

				// Test POST
				apiFetch( ApiUtils.plugin.checkApi( url, true ) )
					.then( () => {
						setApiTest( ( prev ) => ( {
							...prev,
							[ id ]: {
								...prev[ id ],
								POST: { status: STATUS_OK },
							},
						} ) );
					} )
					.catch( () => {
						setApiTest( ( prev ) => ( {
							...prev,
							[ id ]: {
								...prev[ id ],
								POST: { status: STATUS_FAIL },
							},
						} ) );
					} );
			}, 1000 );
		}
	};

	const onTry = () => {
		const untested = Object.keys( routes )
			.map( ( id ) => {
				const url = routes[ id ];
				return url ? { id, url } : null;
			} )
			.filter( ( item ): item is { id: string; url: string } => item !== null );
		checkApi( untested );
	};

	const onRetry = ( ev: MouseEvent< HTMLButtonElement > ) => {
		ev.preventDefault();
		setShowing( false );
		setApiTest( {} );
		onTry();
	};

	const getPercent = ( results: ApiTestResults, routeList: any[] ): number => {
		if ( Object.keys( results ).length === 0 ) {
			return 0;
		}

		const total = routeList.length * 2;
		let finished = 0;

		for ( const key in results ) {
			if ( ! results[ key ] ) {
				continue;
			}
			const result = results[ key ];
			if ( result.GET && result.GET.status !== STATUS_LOADING ) {
				finished++;
			}

			if ( result.POST && result.POST.status !== STATUS_LOADING ) {
				finished++;
			}
		}

		return Math.round( ( finished / total ) * 100 );
	};

	const getApiStatus = ( results: ApiTestResults, routeList: any[], currentRoute: string ): string => {
		const failed = Object.keys( results ).filter( ( key ) => {
			const result = results[ key ];
			return result ? isError( result ) : false;
		} ).length;

		if ( failed === 0 ) {
			return 'ok';
		} else if ( failed < routeList.length ) {
			const currentResult = results[ currentRoute ];
			return currentResult && isWorking( currentResult ) ? STATUS_WARNING_CURRENT : STATUS_WARNING;
		}

		return 'fail';
	};

	const getApiStatusText = ( status: string ): string => {
		if ( status === STATUS_OK ) {
			return __( 'Good', 'search-regex' );
		} else if ( status === STATUS_WARNING_CURRENT ) {
			return __( 'Working but some issues', 'search-regex' );
		} else if ( status === STATUS_WARNING ) {
			return __( 'Not working but fixable', 'search-regex' );
		}

		return __( 'Unavailable', 'search-regex' );
	};

	const onShow = () => {
		setShowing( true );
	};

	const canShowProblem = ( status: string ): boolean => {
		return showing || status === STATUS_FAIL || status === STATUS_WARNING;
	};

	const renderError = ( status: string ) => {
		const showingProblems = canShowProblem( status );
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

				{ ! showingProblems && (
					<p>
						<button className="button-secondary" type="button" onClick={ onShow }>
							{ __( 'Show Problems', 'search-regex' ) }
						</button>
					</p>
				) }
			</div>
		);
	};

	// Run tests on mount
	useEffect( () => {
		onTry();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const routeNames = getRestApi();
	const percent = getPercent( apiTest, routeNames );
	const status = getApiStatus( apiTest, routeNames, current );
	const showProblem = ( percent >= 100 && canShowProblem( status ) ) || showing;
	const statusClass = clsx( {
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
						{ percent >= 100 && getApiStatusText( status ) }
					</span>

					{ percent < 100 && <Spinner /> }
				</div>

				{ percent >= 100 && status !== STATUS_OK && (
					<button className="button button-secondary api-result-retry" onClick={ onRetry }>
						{ __( 'Check Again', 'search-regex' ) }
					</button>
				) }
			</div>

			{ percent >= 100 && status !== STATUS_OK && renderError( status ) }

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
							result={ { GET: result.GET, POST: result.POST } }
							routes={ routes }
							key={ pos }
							isCurrent={ current === String( item.value ) }
							allowChange={ allowChange }
						/>
					);
				} ) }
		</div>
	);
}

export default RestApiStatus;
