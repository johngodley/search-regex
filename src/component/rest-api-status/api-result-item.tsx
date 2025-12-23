import ApiResultError from './api-result-error';
import ApiResultPass from './api-result-pass';

interface ApiTestResult {
	status: string;
	code?: number;
	error?: any;
}

interface ApiResult {
	GET: ApiTestResult;
	POST: ApiTestResult;
}

interface ApiResultItemProps {
	result: ApiResult;
}

const getErrorCode = ( result: ApiTestResult ): number => ( result.code ? result.code : 0 );

function ApiResultItem( { result }: ApiResultItemProps ): JSX.Element[] {
	const details: JSX.Element[] = [];
	const { GET, POST } = result;

	if ( GET.status === POST.status && getErrorCode( GET ) === getErrorCode( POST ) ) {
		if ( GET.status === 'fail' ) {
			details.push( <ApiResultError key="get-post" error={ GET.error } methods={ [ 'GET', 'POST' ] } /> );
		} else {
			details.push( <ApiResultPass key="get-post" methods={ [ 'GET', 'POST' ] } /> );
		}

		return details;
	}

	if ( GET.status === 'fail' ) {
		details.push( <ApiResultError key="get" error={ GET.error } methods={ [ 'GET' ] } /> );
	} else {
		details.push( <ApiResultPass key="get" methods={ [ 'GET' ] } /> );
	}

	if ( POST.status === 'fail' ) {
		details.push( <ApiResultError key="post" error={ POST.error } methods={ [ 'POST' ] } /> );
	} else {
		details.push( <ApiResultPass key="post" methods={ [ 'POST' ] } /> );
	}

	return details;
}

export default ApiResultItem;
