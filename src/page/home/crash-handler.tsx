import { ErrorInfo } from 'react';
import { __ } from '@wordpress/i18n';
import { ExternalLink, Error, createInterpolateElement } from '@wp-plugin-components';
import DebugReport from './debug';
import { getErrorLinks, getErrorDetails } from '../../lib/error-links';

function CrashHandler( error: Error | null, errorInfo: ErrorInfo | null ) {
	const stack = error?.stack || '';
	const componentStack = errorInfo?.componentStack || '';
	return (
		<Error
			errors={ '' }
			renderDebug={ DebugReport }
			type="fixed"
			links={ getErrorLinks() }
			details={ getErrorDetails().concat( [ stack, componentStack ] ) }
			locale={ SearchRegexi10n.locale }
		>
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
						link: <ExternalLink url="https://github.com/johngodley/searchregex/issues" />,
					}
				) }
			</p>
		</Error>
	);
}

export default CrashHandler;
