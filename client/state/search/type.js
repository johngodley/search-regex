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
export const SEARCH_REPLACE_COMPLETE = 'SEARCH_REPLACE_COMPLETE';
export const SEARCH_REPLACE_ALL_COMPLETE = 'SEARCH_REPLACE_ALL_COMPLETE';
export const SEARCH_REPLACE_ALL_MORE = 'SEARCH_REPLACE_ALL_MORE';
export const SEARCH_VALUES = 'SEARCH_VALUES';
export const SEARCH_DELETE_COMPLETE = 'SEARCH_DELETE_COMPLETE';
export const SEARCH_LOAD_ROW_COMPLETE = 'SEARCH_LOAD_ROW_COMPLETE';
export const SEARCH_SAVE_ROW_COMPLETE = 'SEARCH_SAVE_ROW_COMPLETE';
export const SEARCH_TAG_VALUE = 'SEARCH_TAG_VALUE';

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
 * @property {string[]} sourceFlags - Array of source flags
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
