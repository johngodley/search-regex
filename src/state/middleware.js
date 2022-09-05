import { saveAs } from 'file-saver';

/**
 * Internal dependencies
 */
import { SEARCH_START_FRESH, SEARCH_COMPLETE } from '../state/search/type';
import { setPageUrl, removeFromPageUrl, getPageUrl } from '@wp-plugin-lib';
import { PRESET_SELECT } from './preset/type';

/** @typedef {import('./search/type.js').SearchState} SearchState */
/** @typedef {import('./search/type.js').Filter} Filter */

/**
 * @param {Filter} filters
 */
function filterToQuery( filters ) {
	if ( filters.length > 0 ) {
		return JSON.stringify( filters, { arrayFormat: 'brackets', indices: false } );
	}

	return null;
}

/**
 * Set the URL for the search action
 *
 * @param {object} action Action
 * @param {SearchState} searchState Search state
 * @param {object} presetState Preset state
 */
function setUrlForPage( action, searchState, presetState ) {
	const { searchFlags, source, perPage, searchPhrase, filters, view } = action;
	const preset = presetState.presets.find( ( pres ) => pres.id === presetState.currentPreset );

	const defaults = {
		searchPhrase: '',
		searchFlags: [ 'case' ],
		source: [ 'post', 'page' ],
		perPage: 25,
		sub: 'search',
		filters: [],
		view: [],
	};

	if ( preset ) {
		setPageUrl( { page: 'search-regex.php', preset: preset.id }, {} );
	} else {
		setPageUrl(
			{
				page: 'search-regex.php',
				searchPhrase,
				searchFlags,
				source,
				perPage,
				filters: filterToQuery( filters ),
				view: view.join( ',' ),
			},
			defaults
		);
	}
}

function getDataFormat( results, format ) {
	if ( format === 'json' ) {
		return JSON.stringify( results );
	}

	return results.join( '\n' );
}

function getDataFilename( format ) {
	if ( format === 'json' ) {
		return 'export.json';
	}

	if ( format === 'csv' ) {
		return 'export.csv';
	}

	if ( format === 'sql' ) {
		return 'export.sql';
	}

	return 'export.txt';
}

function saveExport( results, format ) {
	const data = getDataFormat( results, format );
	const filename = getDataFilename( format );

	saveAs( new Blob( [ data ] ), filename );
}

export const urlMiddleware = ( store ) => ( next ) => ( action ) => {
	switch ( action.type ) {
		case SEARCH_COMPLETE:
			const { search, results } = store.getState().search;

			if ( action.isSave && action.progress.next === false && search.action === 'export' ) {
				saveExport( results.concat( action.results ), search.actionOption.format ? search.actionOption.format : 'json' );
			}

			break;

		case SEARCH_START_FRESH:
			setUrlForPage( action, store.getState().search, store.getState().preset );
			break;

		case PRESET_SELECT:
			if ( action.currentOnly ) {
				break;
			}

			if ( action.preset ) {
				setPageUrl( { page: 'search-regex.php', preset: action.preset.id }, getPageUrl() );
			} else {
				removeFromPageUrl( 'preset' );
			}
			break;
	}

	return next( action );
};
