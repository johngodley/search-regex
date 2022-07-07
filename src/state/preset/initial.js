/**
 * Internal dependencies
 */

import getPreload from '../../lib/preload';
import { getPageUrl } from '@wp-plugin-lib';

export function getInitialPreset() {
	const query = getPageUrl();
	const { defaultPreset } = SearchRegexi10n.settings;

	return {
		presets: getPreload( 'presets', [] ),
		currentPreset: query.preset ? query.preset : defaultPreset,
		uploadStatus: null,
		isUploading: false,
		clipboardStatus: null,
		clipboard: '',
		error: null,
		errorContext: null,
		imported: 0,
	};
}
