/**
 * External dependencies
 */

import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

const RAW_HIDE_LENGTH = 500;

const doesNeedHiding = ( request ) =>
	request && request.apiFetch.body && request.apiFetch.body.length > RAW_HIDE_LENGTH ? true : false;

function ApiResultRaw( props ) {
	const { request } = props.error;
	const needHiding = doesNeedHiding( request );
	const [ hide, setHide ] = useState( needHiding );
	const toggle = ( ev ) => {
		ev.preventDefault();
		setHide( ! hide );
	};

	if ( request && request.apiFetch.body ) {
		return (
			<>
				{ hide && needHiding && (
					<a className="api-result-hide" onClick={ toggle } href="#">
						{ __( 'Show Full', 'search-regex' ) }
					</a>
				) }
				{ ! hide && needHiding && (
					<a className="api-result-hide" onClick={ toggle } href="#">
						{ __( 'Hide', 'search-regex' ) }
					</a>
				) }
				<pre>
					{ hide ? request.apiFetch.body.substr( 0, RAW_HIDE_LENGTH ) + ' ...' : request.apiFetch.body }
				</pre>
			</>
		);
	}

	return null;
}

export default ApiResultRaw;
