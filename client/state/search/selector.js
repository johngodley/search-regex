/**
 * External dependencies
 */

import { translate as __ } from 'lib/locale';

export const getSearchOptions = () => [
	{
		value: 'regex',
		label: __( 'Regular Expression' ),
	},
	{
		value: 'case',
		label: __( 'Ignore Case' ),
	},
];

export const getPerPage = () => ( [
	{
		value: 25,
		label: __( '25 per page ' ),
	},
	{
		value: 50,
		label: __( '50 per page ' ),
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
] );
