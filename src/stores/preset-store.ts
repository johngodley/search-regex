import { create } from 'zustand';
import { setPageUrl, removeFromPageUrl, getPageUrl } from '@wp-plugin-lib';
import type { PresetValue } from '../types/preset';

interface PresetStore {
	currentPreset: PresetValue | null;
	setCurrentPreset: ( preset: PresetValue | null, currentOnly?: boolean ) => void;
}

export const usePresetStore = create< PresetStore >()( ( set ) => ( {
	currentPreset: null,

	setCurrentPreset: ( preset, currentOnly = false ) => {
		set( { currentPreset: preset } );

		// Update URL unless currentOnly is true
		if ( ! currentOnly ) {
			if ( preset ) {
				setPageUrl( { page: 'search-regex.php', preset: preset.id }, getPageUrl() );
			} else {
				removeFromPageUrl( 'preset' );
			}
		}
	},
} ) );
