import { useState, type MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import type { ApiError, ApiErrorRequest } from '../../wp-plugin-components/error/types';

const RAW_HIDE_LENGTH = 500;

interface ApiResultRawProps {
	error: ApiError;
}

const doesNeedHiding = ( request?: ApiErrorRequest ): boolean =>
	request && request.apiFetch?.body && request.apiFetch.body.length > RAW_HIDE_LENGTH ? true : false;

function ApiResultRaw( props: ApiResultRawProps ): JSX.Element | null {
	const { request } = props.error;
	const needHiding = doesNeedHiding( request );
	const [ hide, setHide ] = useState( needHiding );
	const toggle = ( ev: MouseEvent< HTMLButtonElement > ): void => {
		ev.preventDefault();
		setHide( ! hide );
	};

	if ( request?.apiFetch?.body ) {
		return (
			<>
				{ hide && needHiding && (
					<button className="api-result-hide" onClick={ toggle } type="button">
						{ __( 'Show Full', 'search-regex' ) }
					</button>
				) }
				{ ! hide && needHiding && (
					<button className="api-result-hide" onClick={ toggle } type="button">
						{ __( 'Hide', 'search-regex' ) }
					</button>
				) }
				<pre>
					{ hide ? request.apiFetch.body.substring( 0, RAW_HIDE_LENGTH ) + ' ...' : request.apiFetch.body }
				</pre>
			</>
		);
	}

	return null;
}

export default ApiResultRaw;
