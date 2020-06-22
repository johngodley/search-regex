/**
 * Internal dependencies
 */
import { SEARCH_START_FRESH } from 'state/search/type';
import { setPageUrl, removeFromPageUrl, getPageUrl } from 'wp-plugin-lib/wordpress-url';
import { removePostTypes } from 'lib/sources';
import { PRESET_SELECT } from './preset/type';

/**
 * Set the URL for the search action
 *
 * @param {object} action Action
 * @param {object} searchState Search state
 * @param {object} presetState Preset state
 */
function setUrlForPage( action, searchState, presetState ) {
	const { tagValues } = searchState;
	const { searchPhrase, searchFlags, source, sourceFlags, perPage } = action;
	const preset = presetState.presets.find( ( pres ) => pres.id === presetState.currentPreset );

	const defaults = {
		searchPhrase: '',
		searchFlags: [],
		source: [],
		sourceFlags: [],
		perPage: 25,
		sub: 'search',
		...( preset ? preset.search : {} ),
		tagValues: [],
	};

	// Remove custom page types if 'posts' is included
	const filteredSource = removePostTypes( source, searchState.sources );

	setPageUrl(
		{
			page: 'search-regex.php',
			searchPhrase: Object.keys( tagValues ).length === 0 ? searchPhrase : '',
			searchFlags,
			source: filteredSource,
			sourceFlags,
			perPage,
			preset: presetState.currentPreset,
			...tagValues,
		},
		defaults
	);
}

export const urlMiddleware = ( store ) => ( next ) => ( action ) => {
	switch ( action.type ) {
		case SEARCH_START_FRESH:
			setUrlForPage( action, store.getState().search, store.getState().preset );
			break;

		case PRESET_SELECT:
			if ( action.presetId ) {
				setPageUrl( { page: 'search-regex.php', preset: action.presetId }, getPageUrl() );
			} else {
				removeFromPageUrl( 'preset' );
			}
			break;
	}

	return next( action );
};
