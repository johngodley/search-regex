import { __ } from '@wordpress/i18n';

interface ExportOption {
	value: string;
	label: string;
}

interface Action {
	label: string;
	value: string;
	desc: string;
	disabled?: boolean;
}

export function getExportOptions(): ExportOption[] {
	return [
		{
			value: 'json',
			label: __( 'JSON', 'search-regex' ),
		},
		{
			value: 'csv',
			label: __( 'CSV', 'search-regex' ),
		},
		{
			value: 'sql',
			label: __( 'SQL', 'search-regex' ),
		},
	];
}

export function getActions( _hasGlobalSearch: boolean, hasSingleSource: boolean ): Action[] {
	return [
		{
			label: __( 'No action', 'search-regex' ),
			value: '',
			desc: __( 'Just show matching results.', 'search-regex' ),
		},
		{
			label: __( 'Global Text Replace', 'search-regex' ),
			value: 'replace',
			desc: __( 'Replace the global search values.', 'search-regex' ),
		},
		{
			label: __( 'Modify Matches', 'search-regex' ),
			value: 'modify',
			desc: __( 'Perform changes to specific values of the matching results.', 'search-regex' ),
		},
		{
			label: __( 'Export Matches', 'search-regex' ),
			value: 'export',
			desc: __( 'Export matching results to JSON, CSV, or SQL.', 'search-regex' ),
			disabled: ! hasSingleSource,
		},
		{
			label: __( 'Delete Matching Rows', 'search-regex' ),
			value: 'delete',
			desc: __( 'Delete matching rows.', 'search-regex' ),
		},
		{
			label: __( 'Run Action', 'search-regex' ),
			value: 'action',
			desc: __( 'Run a WordPress action for each matching result.', 'search-regex' ),
		},
	];
}
