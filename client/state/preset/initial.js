/**
 * Internal dependencies
 */

import getPreload from 'lib/preload';
import { getPageUrl } from 'wp-plugin-lib/wordpress-url';

export function getInitialPreset() {
	const query = getPageUrl();

	return {
		presets: getPreload( 'presets', [] ),
		currentPreset: query.preset ? query.preset : '',
		uploadStatus: null,
		isUploading: false,
		clipboardStatus: null,
		clipboard: '',
		error: null,
		errorContext: null,
		imported: 0,
	};
}
