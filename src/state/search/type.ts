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

export type {
	SearchValues,
	SearchSource,
	SearchSourceGroup,
	Result,
	ResultColumn,
	ContextList,
	ContextText,
	Match,
	Schema,
	SchemaColumn,
	Filter,
	FilterItem,
	ModifyColumn,
	ModifyStringColumn,
	ModifyDateColumn,
	ModifyIntegerColumn,
	ModifyMemberColumn,
	SearchState,
	SelectOption,
} from '../../types/search';

export type SetReplace = ( value: unknown ) => void;
