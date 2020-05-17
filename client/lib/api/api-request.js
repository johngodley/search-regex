/**
 * Internal dependencies
 */

import { getErrorCode, getErrorMessage } from './api-error';
import { setApiNonce } from './api-url';
import { getApiUrl } from './api-url';

const getAction = request =>
	request.url.replace( getApiUrl(), '' ).replace( /[\?&]_wpnonce=[a-f0-9]*/, '' ) +
	' ' +
	request.method.toUpperCase();

export const getApi = request => {
	// Remember action for later error reporting
	request.action = getAction( request );

	return fetch( request.url, request )
		.then( data => {
			if ( ! data || ! data.status ) {
				throw { message: 'No data or status object returned in request', code: 0 };
			}

			if ( data.status && data.statusText !== undefined ) {
				request.status = data.status;
				request.statusText = data.statusText;
			}

			if ( data.headers.get( 'x-wp-nonce' ) ) {
				setApiNonce( data.headers.get( 'x-wp-nonce' ) );
			}

			return data.text();
		} )
		.then( text => {
			request.raw = text;

			try {
				const json = JSON.parse( text.replace( /\ufeff/, '' ) );

				if ( request.status && request.status !== 200 ) {
					throw {
						message: getErrorMessage( json ),
						code: getErrorCode( json ),
						request,
						data: json.data ? json.data : null,
					};
				}

				if ( json === 0 ) {
					throw { message: 'Failed to get data', code: 'json-zero' };
				}

				return json;
			} catch ( error ) {
				error.request = request;
				error.code = error.code || error.name;
				throw error;
			}
		} );
};
