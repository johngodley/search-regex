import { DecodeError } from '@wp-plugin-components';
import type { ApiError } from '../../wp-plugin-components/error/types';
import ApiResultRaw from './api-result-raw';
import { getErrorLinks } from '../../lib/error-links';

const getApiErrorName = ( error: ApiError ): string | null => {
	if ( error.code ) {
		return String( error.code );
	}

	return null;
};

interface ApiResultErrorProps {
	error: ApiError;
	methods: string[];
}

function ApiResultError( { error, methods }: ApiResultErrorProps ): JSX.Element {
	const name = getApiErrorName( error );

	return (
		<div className="api-result-log_details" key={ methods.join() }>
			<p>
				<span className="dashicons dashicons-no" />
			</p>

			<div>
				<p>
					{ methods.map( ( method, key ) => (
						<span key={ key } className="api-result-method_fail">
							{ method } { error.request?.status }
						</span>
					) ) }

					{ name && <strong>{ name }: </strong> }
					{ error.message }
				</p>

				<DecodeError error={ error } links={ getErrorLinks() } locale="en" />
				<ApiResultRaw error={ error } />
			</div>
		</div>
	);
}

export default ApiResultError;
