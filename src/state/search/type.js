/**
 * Internal dependencies
 */

export const SEARCH_START_FRESH = 'SEARCH_START_FRESH';
export const SEARCH_START_MORE = 'SEARCH_START_MORE';
export const SEARCH_FAIL = 'SEARCH_FAIL';
export const SEARCH_COMPLETE = 'SEARCH_COMPLETE';
export const SEARCH_REPLACE_ALL = 'SEARCH_REPLACE_ALL';
export const SEARCH_REPLACE_ROW = 'SEARCH_REPLACE_ROW';
export const SEARCH_CANCEL = 'SEARCH_CANCEL';
export const SEARCH_PERFORM_FRESH = 'SEARCH_PERFORM_FRESH';
export const SEARCH_PERFORM_MORE = 'SEARCH_PERFORM_MORE';
export const SEARCH_VALUES = 'SEARCH_VALUES';
export const SEARCH_DELETE_COMPLETE = 'SEARCH_DELETE_COMPLETE';
export const SEARCH_LOAD_ROW_COMPLETE = 'SEARCH_LOAD_ROW_COMPLETE';
export const SEARCH_LABEL = 'SEARCH_LABEL';

export const SEARCH_FORWARD = 'forward';
export const SEARCH_BACKWARD = 'backward';

/**
 * All values associated with a search and replace.
 *
 * @typedef SearchValues
 * @type
 * @property {string} searchPhrase - Phrase to search for
 * @property {string[]} searchFlags - Array of search flags
 * @property {string[]} source - Array of sources to search
 * @property {string} replacement - Replacement phrase
 * @property {number} perPage - Per page
 */

/**
 * Search source
 *
 * @typedef SearchSource
 * @type
 * @property {string} description - Description of this source
 * @property {string} label - Title for this source
 * @property {string} name - Name for this source
 * @property {string} type - Which group the source belongs to
 */

/**
 * Search source group
 *
 * @typedef SearchSourceGroup
 * @type
 * @property {string} name - Name of source
 * @property {string} label - Text label of source
 * @property {SearchSource[]} sources - Array of SearchSource objects
 */

/**
 * Match result for a single row
 *
 * @typedef Result
 * @type
 * @property {number} match_count - Number of matches for this row
 * @property {string} source_name - Visual name for source
 * @property {string} source_type - Actual name of source
 * @property {string} title - Title of the result
 * @property {ResultColumn[]} columns - Columns
 * @property {object[]} actions - Actions
 */

/**
 * Match result for a single row column
 *
 * @typedef ResultColumn
 * @type
 * @property {string} column_id - Column ID
 * @property {string} column_label - Text label of column
 * @property {ContextList[]|ContextText[]} contexts - Array of contexts of `column_type`, cropped to a max size
 * @property {number} context_count - Total number of contexts without cropping
 * @property {number} match_count - Total number of matches
 */

/**
 * A matched column within a row
 *
 * @typedef ContextList
 * @type
 * @property {string} context_id - Unique ID for this context
 * @property {'value'|'delete'|'replace'|'add'} type - Type of context item
 * @property {string} value - Value
 * @property {string} value_label - Value label
 * @property {string} replacement - Replacement
 * @property {string} replacement_value - Replacement label
 */

/**
 * A matched text column within a row
 *
 * @typedef ContextText
 * @type
 * @property {string} context_id - Unique ID for this context
 * @property {number} match_count - Number of matches without cropping
 * @property {string} context - Context string the matches all exist within
 * @property {Match[]} matches - Matched strings, cropped to a maximum
 */

/**
 * A matched item of text
 *
 * @typedef Match
 * @type
 * @property {number} context_offset - Offset within ContextText.context that the match begins
 * @property {number} pos_id - Offset within the whole column that the match begins
 * @property {string} match - Matched text
 * @property {string} replacement - Replacement text
 * @property {string} captures - Captured text for each regex capture
 */

/**
 * Search schema
 *
 * @typedef Schema
 * @type
 * @property {string} name - Name of table
 * @property {string} type - Type of table
 * @property {SchemaColumn[]} columns - Columns in this table
 */

/**
 * Search schema column
 *
 * @typedef SchemaColumn
 * @type
 * @property {string} column - Name of column
 * @property {('integer'|'string'|'member'|'date')} type - Column type
 * @property {string} title - Title for the column
 * @property {string|SelectOption[]} [api] - API options
 */

/**
 * Filter item
 *
 * @typedef FilterItem
 * @type
 * @property {string} column
 * @property {string} [logic]
 * @property {string} [value]
 * @property {number} [startValue]
 * @property {number} [endValue]
 */

/**
 * Filter
 *
 * @typedef Filter
 * @type
 * @property {string} type - Source name
 * @property {FilterItem[]} items - Columns in this filter
 */

/**
  * Modify column
  * @typedef {ModifyStringColumn|ModifyDateColumn|ModifyIntegerColumn|ModifyMemberColumn} ModifyColumn
  */

/**
 * Modify string column
 *
 * @typedef ModifyStringColumn
 * @type
 * @property {string} column - Column name
 * @property {'set'|'replace'|'regex'} operation
 * @property {string} setValue
 * @property {string} [replaceValue]
 */

/**
 * Modify date column
 *
 * @typedef ModifyDateColumn
 * @type
 * @property {string} column - Column name
 * @property {'set'|'increment'|'decrement'} operation
 * @property {string} value
 * @property {'second'|'hour'|'day'|'month'|'year'} [unit]
 */

/**
 * Modify integer column
 *
 * @typedef ModifyIntegerColumn
 * @type
 * @property {string} column - Column name
 * @property {'set'|'increment'|'decrement'} operation
 * @property {string} value
 */

/**
 * Modify member column
 *
 * @typedef ModifyMemberColumn
 * @type
 * @property {string} column - Column name
 * @property {'set'|'include'|'exclude'} operation
 * @property {string[]} values
 */

/**
 * Search state
 * @typedef SearchState
 * @type
 * @property {object[]} results
 * @property {object[]} replacing
 * @property {boolean} replaceAll
 * @property {object} search
 * @property {string|null} searchDirection
 * @property {number} requestCount
 * @property {} totals
 * @property {} progress
 * @property {} status
 * @property {boolean} showLoading
 * @property {SearchSourceGroup} sources
 * @property {boolean} canCancel
 * @property {Schema[]} schema
 */

/**
 * Change the replacement value
 *
 * @callback SetReplace
 * @param {object} value
 */
