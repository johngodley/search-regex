/**
 * External dependencies
 */

import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import { STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';
import { SEARCH_FORWARD, SEARCH_BACKWARD } from 'state/search/type';
import { getPageUrl } from 'wp-plugin-lib/wordpress-url';
import { getDefaultPresetValues } from 'state/preset/selector';

/** @typedef {import('./type.js').SearchValues} SearchValues */
/** @typedef {import('./type.js').SearchSourceGroup} SearchSourceGroup */
/** @typedef {import('./type.js').Schema} Schema */
/** @typedef {import('./type.js').Filter} Filter */

/**
 * Return all the search flags
 */
export const getAvailableSearchFlags = () => [
	{
		value: 'regex',
		label: __( 'Regular Expression' ),
		alt: __( 'Regex' ),
	},
	{
		value: 'case',
		label: __( 'Ignore Case' ),
		alt: __( 'Case' ),
	},
	{
		value: 'multi',
		label: __( 'Multiline' ),
		alt: __( 'Multi' ),
	},
];

/**
 * Return all the per-page values
 */
export const getAvailablePerPage = () => [
	{
		value: 25,
		label: __( '25 per page' ),
	},
	{
		value: 50,
		label: __( '50 per page' ),
	},
	{
		value: 100,
		label: __( '100 per page' ),
	},
	{
		value: 250,
		label: __( '250 per page' ),
	},
	{
		value: 500,
		label: __( '500 per page' ),
	},
	{
		value: 1000,
		label: __( '1000 per page' ),
	},
	{
		value: 2000,
		label: __( '2000 per page' ),
	},
];

/**
 * Return true if the state is complete or has no status, false otherwise
 * @param {Object} state Current state
 * @returns {Boolean}
 */
export const isAlreadyFinished = ( state ) => state.status === STATUS_COMPLETE || state.status === null || state.status === STATUS_FAILED;

/**
 * Return the total replacements based on the state and action
 * The total will be the number of matched rows, if it exists. Otherwise it will be the number of returned rows.
 * Failing that it will be the total in the state.
 *
 * @param {Object} state Current state
 * @param {Object} action Current action
 */
export function getReplaceTotal( state, action ) {
	if ( action.totals.matched_rows ) {
		return action.totals.matched_rows;
	}

	if ( action.totals.rows ) {
		return action.totals.rows;
	}

	return state.totals.rows;
}

/**
 * Has the search finished?
 * @param {Object} action Current action
 * @param {} results Array of results
 * @param {String} direction Current search direction
 * @returns {Boolean}
 */
export function isComplete( action, results, direction ) {
	// If going forward and no next, then complete
	if ( direction === SEARCH_FORWARD && action.progress.next === false ) {
		return true;
	}

	// If going back and no previous then complete
	if ( direction === SEARCH_BACKWARD && action.progress.previous === false ) {
		return true;
	}

	return false;
}

/**
 * Is this an advanced (regex) search? This is when any filter OR the search flags have regex enabled
 *
 * @param {SearchValues} search Search object
 * @returns {Boolean}
 */
export function isAdvancedSearch( search ) {
	const { searchFlags, searchPhrase, filters } = search;

	// Look at the global search
	if ( searchFlags.indexOf( 'regex' ) !== -1 && searchPhrase.length > 0 ) {
		return true;
	}

	for ( let index = 0; index < filters.length; index++ ) {
		const filter = filters[ index ];

		for ( let itemIndex = 0; itemIndex < filter.items.length; itemIndex++ ) {
			const item = filter.items[ itemIndex ];

			if ( item.flags && item.flags.indexOf( 'regex' ) !== -1 ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Apply any processing to the search values to make them suitable for the API
 *
 * @param {SearchValues} values - Search value object
 * @returns {SearchValues}
 */
export function getSearchValues( values ) {
	return {
		...values,
		replacement: getReplacement( values.replacement ),
		perPage: values.perPage === -1 ? 250 : values.perPage,
		...( values.perPage === -1 && values.limit ? { limit: null } : {} ),
	};
}

/**
 * In the client we represent 'delete' as a null value, but the API requires this to be an empty string
 * @param {String|null} replacement Replacement value, or `null` to remove
 * @returns String
 */
export function getReplacement( replacement ) {
	return replacement ? replacement : '';
}

/**
 * Get default search values
 *
 * @returns {SearchValues}
 */
export function getDefaultSearch() {
	return {
		searchPhrase: '',
		searchFlags: [ 'case' ],
		replacement: '',

		source: [ 'posts' ],
		perPage: 25,
		filters: getDefaultFilters( 'posts' ),

		action: '',
		actionOption: [],

		view: [],
	};
}

/**
 * Get default filters for a source
 * @param {string} source Source
 * @returns {Filter[]}
 */
export function getDefaultFilters( source ) {
	if ( source === 'posts' ) {
		return [
			{
				type: 'posts',
				items: [
					{
						column: 'post_type',
						logic: 'include',
						values: [ 'post', 'page' ],
					},
				],
			},
		];
	}

	if ( source === 'comment' ) {
		return [
			{
				type: 'comment',
				items: [
					{
						column: 'comment_approved',
						logic: 'exclude',
						values: [ 'spam' ],
					},
				],
			},
		];
	}

	return [];
}

/**
 * Get search values from query parameters
 *
 * @param {object|null} [queryParams] - Optional query object, otherwise uses browser URL
 * @returns {SearchValues}
 */
export function getQuerySearchParams( queryParams = null ) {
	const query = queryParams ? queryParams : getPageUrl();
	const search = {};
	const params = {
		searchphrase: 'searchPhrase',
		searchflags: 'searchFlags',
		source: 'source',
		replacement: 'replacement',
		perpage: 'perPage',
		filters: 'filters',
		view: 'view',
	};

	// Copy any query params across
	Object.keys( params ).forEach( ( key ) => {
		if ( query[ key ] ) {
			search[ params[ key ] ] = query[ key ];
		}
	} );

	if ( search[ 'filters' ] ) {
		try {
			search[ 'filters' ] = JSON.parse( search[ 'filters' ] );
		} catch ( error ) {
			search[ 'filters' ] = [];
		}
	}

	if ( search[ 'view' ] ) {
		search[ 'view' ] = search[ 'view' ].split( ',' );
	}

	return search;
}

/**
 * Get a named schema
 * @param {Schema[]} schemas - Schemas
 * @param {string} source - Source name
 * @returns {Schema}
 */
export function getSchema( schemas, source ) {
	return schemas.find( ( scheme ) => scheme.type === source );
}

export function getSchemaColumn( columns, name ) {
	return columns.find( ( scheme ) => scheme.column === name );
}

export function getSchemaSourceColumn( schemas, source, column ) {
	const schemaSource = getSchema( schemas, source );

	if ( schemaSource ) {
		return getSchemaColumn( schemaSource.columns, column );
	}

	return null;
}

export function getSearchFromPreset( preset ) {
	if ( preset ) {
		return {
			...preset.search,
			...getDefaultPresetValues( preset ),
		};
	}

	return getDefaultSearch();
}

/**
 * Get a filter for a column
 * @param {string} type - Column type
 * @param {Schema} schema - Schema
 * @returns {Filter}
 */
export function getFilterForType( type, schema ) {
	const first = schema.columns[ 0 ];

	return {
		type,
		items: [ { column: first.column } ],
	};
}

export function getLabel( labels, labelId, labelValue ) {
	for ( let index = 0; index < labels.length; index++ ) {
		if ( labels[ index ].value === labelId ) {
			return labels[ index ].label;
		}
	}

	return labelValue || labelId;
}

/**
 * Merge all the sources into a filter dropdown
 * @param {SearchSourceGroup[]} sources - All sources
 * @param {Schema[]} schema - Schema
 * @returns {SelectOption[]}
 */
export function getSearchOptionsForSources( sources, schema ) {
	return sources.map( ( sourceName ) => ( {
		label: schema.find( ( scheme ) => scheme.type === sourceName ).name,
		value: sourceName,
	} ) );
}

function getActionDefaultsResult( column, schema ) {
	if ( ! schema ) {
		return {};
	}

	if ( schema.type === 'member' ) {
		return {
			values: column.contexts
				.filter( ( item ) => item.type !== 'empty' )
				.map( ( item ) => ( item.replacement_value ? item.replacement_value : item.value ) ),
		};
	}

	return {};
}

function getActionDefaults( schema ) {
	if ( schema.type === 'member' && Array.isArray( schema.options ) ) {
		return { values: [ schema.options[ 0 ].value ] };
	}

	return {};
}

/**
 * Get a new column action
 * @param {import('./type.js').ResultColumn} column
 * @param {import('./type.js').Schema} schema
 * @returns {import('./type.js').ModifyColumn}
 */
export function getNewAction( column_id, schema ) {
	const columnSchema = schema.columns ? getSchemaColumn( schema.columns, column_id ) : schema;
	const defaults = getActionDefaults( columnSchema );

	return { column: columnSchema.column, operation: '', source: schema.type, ...defaults };
}

export function getNewActionFromResult( column, columnSchema, source ) {
	const defaults = getActionDefaultsResult( column, columnSchema );

	return { column: columnSchema.column, operation: '', source, ...defaults };
}
