/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

function DatabaseError( props ) {
	const { error, onClear } = props;
	const debug = error.jsonData;
	const email = 'mailto:john@searchregex.com?subject=Search%20Regex%20Query%20Error&body=' + encodeURIComponent( debug );
	const github =
		'https://github.com/johngodley/search-regex/issues/new?title=Search%20Regex%20Query%20Error&body=' +
		encodeURIComponent( '```\n' + debug + '\n```\n\n' );

	return (
		<div className="wpl-error">
			<div className="closer" onClick={ onClear }>
				<span className="dashicons dashicons-no-alt" />
			</div>
			<h2>{ __( 'Query Problem', 'search-regex' ) }</h2>
			<p>
				{ __(
					"A problem occurred with your last query. This is likely caused by a combination of search filters that haven't been handled properly."
				) }
			</p>

			<p>
				<code>{ error.jsonData }</code>
			</p>

			<h3>{ __( 'What do I do next?', 'search-regex' ) }</h3>
			<p>
				<a href={ github } className="button-primary">
					{ __( 'Create An Issue', 'search-regex' ) }
				</a>{' '}
				<a href={ email } className="button-secondary">
					{ __( 'Email', 'search-regex' ) }
				</a>
			</p>
		</div>
	);
}

export default DatabaseError;
