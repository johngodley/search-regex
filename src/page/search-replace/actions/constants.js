/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

export function getExportOptions() {
	return [
		{
			value: 'json',
			label: __( 'JSON' ),
		},
		{
			value: 'csv',
			label: __( 'CSV' ),
		},
		{
			value: 'sql',
			label: __( 'SQL' ),
		},
	];
}

/**
 *
 * @param {boolean} hasGlobalSearch
 * @param {boolean} hasSingleSource
 */
export function getActions( hasGlobalSearch, hasSingleSource ) {
	return [
		{
			label: __( 'No action' ),
			value: '',
			desc: __( 'Just show matching results.' ),
		},
		{
			label: __( 'Global Text Replace' ),
			value: 'replace',
			desc: __( 'Replace the global search values.' ),
		},
		{
			label: __( 'Modify Matches' ),
			value: 'modify',
			desc: __( 'Perform changes to specific values of the matching results.' ),
		},
		{
			label: __( 'Export Matches' ),
			value: 'export',
			desc: __( 'Export matching results to JSON, CSV, or SQL.' ),
			disabled: ! hasSingleSource,
		},
		{
			label: __( 'Delete Matches' ),
			value: 'delete',
			desc: __( 'Delete matching results.' ),
		},
		{
			label: __( 'Run Action' ),
			value: 'action',
			desc: __( 'Run a WordPress action for each matching result.' ),
		},
	];
}
