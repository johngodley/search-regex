/**
 * All values associated with a search and replace.
 */
export interface SearchValues {
	searchPhrase?: string;
	searchFlags?: string[];
	source?: string[];
	replacement?: string;
	perPage?: number;
	filters?: Filter[];
	[ key: string ]: any;
}

/**
 * Search source
 */
export interface SearchSource {
	description: string;
	label: string;
	name: string;
	type: string;
}

/**
 * Search source group
 */
export interface SearchSourceGroup {
	name: string;
	label: string;
	sources: SearchSource[];
}

/**
 * A matched item of text
 */
export interface Match {
	context_offset: number;
	pos_id: number;
	match: string;
	replacement: string;
	captures: string[];
}

/**
 * A matched text column within a row
 */
export interface ContextText {
	context_id: string;
	match_count: number;
	context: string;
	matches: Match[];
}

/**
 * A matched column within a row
 */
export interface ContextList {
	context_id: string;
	type: 'value' | 'delete' | 'replace' | 'add';
	value: string;
	value_label: string;
	replacement: string;
	replacement_value: string;
	key?: string | { context?: string; value?: string };
}

/**
 * Match result for a single row column
 */
export interface ResultColumn {
	column_id: string;
	column_label: string;
	contexts: ContextList[] | ContextText[];
	context_count: number;
	match_count: number;
}

/**
 * Match result for a single row
 */
export interface Result {
	row_id: string;
	match_count: number;
	source_name: string;
	source_type: string;
	title: string;
	columns: ResultColumn[];
	actions: Record< string, string | undefined >;
}

/**
 * Search schema column
 */
export interface SchemaColumn {
	column?: string;
	type: 'integer' | 'string' | 'member' | 'date' | 'keyvalue';
	title?: string;
	api?: string | SelectOption[];
	options?: 'api' | SelectOption[];
	preload?: boolean;
	multiline?: boolean;
	multiple?: boolean;
	joined_by?: string;
	join?: string;
	length?: number;
	[ key: string ]: any;
}

/**
 * Search schema
 */
export interface Schema {
	name: string;
	type: string;
	columns: SchemaColumn[];
	source?: string;
}

/**
 * Filter item
 */
export interface FilterItem {
	column: string;
	logic?: string;
	value?: string;
	startValue?: number | string;
	endValue?: number | string;
	flags?: string[];
	values?: string[];
	key?: string;
	keyLogic?: string;
	keyFlags?: string[];
	valueLogic?: string;
	valueFlags?: string[];
}

/**
 * Filter
 */
export interface Filter {
	type: string;
	items: FilterItem[];
}

/**
 * Modify string column
 */
export interface ModifyStringColumn {
	column: string;
	source?: string;
	operation: 'set' | 'replace' | 'regex';
	setValue?: string;
	replaceValue?: string;
	searchValue?: string;
	searchFlags?: string[];
}

/**
 * Modify date column
 */
export interface ModifyDateColumn {
	column: string;
	source?: string;
	operation: 'set' | 'increment' | 'decrement';
	value: string | Date;
	unit?: 'second' | 'hour' | 'day' | 'month' | 'year';
}

/**
 * Modify integer column
 */
export interface ModifyIntegerColumn {
	column: string;
	source?: string;
	operation: 'set' | 'increment' | 'decrement';
	value: string;
}

/**
 * Modify member column
 */
export interface ModifyMemberColumn {
	column: string;
	source?: string;
	operation: 'set' | 'include' | 'exclude';
	values: string[];
	label?: string | string[];
}

/**
 * Modify column union type
 */
export type ModifyColumn = ModifyStringColumn | ModifyDateColumn | ModifyIntegerColumn | ModifyMemberColumn;

/**
 * Search state
 */
export interface SearchState {
	results: unknown[];
	replacing: unknown[];
	replaceAll: boolean;
	search: unknown;
	searchDirection: string | null;
	requestCount: number;
	totals: unknown;
	progress: unknown;
	status: unknown;
	showLoading: boolean;
	sources: SearchSourceGroup;
	canCancel: boolean;
	schema: Schema[];
}

/**
 * SelectOption used across components
 */
export interface SelectOption {
	label: string;
	value: string | SelectOption[];
	disabled?: boolean;
}
