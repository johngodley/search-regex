import type { PresetValue } from '../../types/preset';
import getPreload from '../../lib/preload';
import { getPageUrl } from '@wp-plugin-lib';

declare const SearchRegexi10n: {
	settings: {
		defaultPreset: string;
	};
};

interface PresetState {
	presets: PresetValue[];
	currentPreset: string;
	uploadStatus: string | null;
	isUploading: boolean;
	clipboardStatus: string | null;
	clipboard: string;
	error: unknown | null;
	errorContext: string | null;
	imported: number;
}

export function getInitialPreset(): PresetState {
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
