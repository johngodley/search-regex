import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wp-plugin-components';
import ApiResultItem from './api-result-item';

interface ApiTestResult {
	status: string;
}

interface ApiResult {
	GET: ApiTestResult;
	POST: ApiTestResult;
}

interface ApiItem {
	value: string;
	text: string;
}

interface ApiResultProps {
	item: ApiItem;
	result: ApiResult;
	routes: Record< string, string >;
	isCurrent: boolean;
	allowChange: boolean;
}

function getApiNonce(): string {
	return ( window as any ).SearchRegexi10n.api.WP_API_nonce as string;
}

const isLoading = ( result: ApiResult ): boolean =>
	Object.keys( result ).length === 0 || result.GET.status === 'loading' || result.POST.status === 'loading';

function ApiResult( { item, result, routes, isCurrent, allowChange }: ApiResultProps ): JSX.Element | null {
	if ( isLoading( result ) ) {
		return null;
	}

	return (
		<div className="api-result-log">
			<form
				className="api-result-select"
				action={ ( ( window as any ).SearchRegexi10n.pluginRoot as string ) + '&sub=support' }
				method="POST"
			>
				{ allowChange && ! isCurrent && (
					<input
						type="submit"
						className="button button-secondary"
						value={ __( 'Switch to this API', 'search-regex' ) }
					/>
				) }
				{ allowChange && isCurrent && <span>{ __( 'Current API', 'search-regex' ) }</span> }

				<input type="hidden" name="rest_api" value={ item.value } />
				<input type="hidden" name="_wpnonce" value={ getApiNonce() } />
				<input type="hidden" name="action" value="rest_api" />
			</form>

			<h4>{ item.text }</h4>

			<p>
				URL:{ ' ' }
				<code>
					<ExternalLink url={ routes[ item.value ] }>{ routes[ item.value ] }</ExternalLink>
				</code>
			</p>

			<ApiResultItem result={ result } />
		</div>
	);
}

export default ApiResult;
