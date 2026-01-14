import { ErrorInfo } from 'react';
import { __ } from '@wordpress/i18n';
import { ExternalLink, Error, createInterpolateElement } from '@wp-plugin-components';
import DebugReport from './debug';
import { getErrorLinks, getErrorDetails } from '../../lib/error-links';

/**
 * Check if the error is caused by a browser extension modifying the DOM.
 * Common culprits: Google Translate, Grammarly, ad blockers, password managers.
 *
 * @param error The error to check.
 */
function isBrowserExtensionError( error: Error | null ): boolean {
	const message = error?.message || '';
	const stack = error?.stack || '';

	// Check for extension URLs in the stack trace
	if ( stack.includes( 'chrome-extension://' ) || stack.includes( 'moz-extension://' ) ) {
		return true;
	}

	// Check for DOM manipulation errors typically caused by extensions
	return (
		message.includes( "Failed to execute 'removeChild'" ) ||
		message.includes( "Failed to execute 'insertBefore'" ) ||
		message.includes( "Failed to execute 'appendChild'" ) ||
		message.includes( 'The node to be removed is not a child of this node' )
	);
}

function BrowserExtensionWarning() {
	return (
		<>
			<p>
				<strong>
					{ __(
						'This error is typically caused by a browser extension (such as Google Translate, Grammarly, ad blocker, etc) modifying the page.',
						'search-regex'
					) }
				</strong>
			</p>
			<p>{ __( 'To fix this:', 'search-regex' ) }</p>
			<ul style={ { listStyle: 'disc', marginLeft: '20px' } }>
				<li>{ __( 'Disable browser extensions for this page', 'search-regex' ) }</li>
				<li>{ __( 'Try using a different browser or incognito/private mode', 'search-regex' ) }</li>
			</ul>
		</>
	);
}

function DefaultCrashMessage() {
	return (
		<>
			<p>
				{ __(
					'Search Regex is not working. Try clearing your browser cache and reloading this page.',
					'search-regex'
				) }{ ' ' }
				&nbsp;
				{ __(
					'If you are using a page caching plugin or service (CloudFlare, OVH, etc) then you can also try clearing that cache.',
					'search-regex'
				) }
			</p>

			<p>
				{ createInterpolateElement(
					__(
						"If that doesn't help, open your browser's error console and create a {{link}}new issue{{/link}} with the details.",
						'search-regex'
					),
					{
						link: <ExternalLink url="https://github.com/johngodley/search-regex/issues" />,
					}
				) }
			</p>
		</>
	);
}

function CrashHandler( error: Error | null, errorInfo: ErrorInfo | null ) {
	const stack = error?.stack || '';
	const componentStack = errorInfo?.componentStack || '';
	const isExtensionError = isBrowserExtensionError( error );

	return (
		<Error
			errors={ '' }
			renderDebug={ DebugReport }
			type="fixed"
			links={ getErrorLinks() }
			details={ getErrorDetails().concat( [ '', stack, componentStack ] ) }
			locale={ SearchRegexi10n.locale }
		>
			{ isExtensionError ? <BrowserExtensionWarning /> : <DefaultCrashMessage /> }
		</Error>
	);
}

export default CrashHandler;
