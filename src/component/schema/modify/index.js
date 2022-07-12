/**
 * External dependencies
 */

import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { apiFetch } from '@wp-plugin-lib';
import SearchRegexApi from '../../../lib/api-request';
import ModifyType from './types';
import './style.scss';

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {} props.schema - Column schema
 * @param {} props.column - Column
 * @param {} props.onChange
 * @param {} [props.onRemove]
 */
function Modify( props ) {
	const { disabled, schema, column, onRemove, onChange } = props;

	function fetchData( value ) {
		return apiFetch( SearchRegexApi.source.complete( column.source, column.column, value ) );
	}

	return (
		<div className="searchregex-modify">
			<div className="searchregex-modify__name">
				<span>{ schema.title }</span>

				{ onRemove && <span onClick={ disabled ? () => {} : onRemove } className={ classnames( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) } /> }
			</div>

			<ModifyType
				disabled={ disabled }
				schema={ schema }
				item={ column }
				onChange={ onChange }
				fetchData={ fetchData }
			/>
		</div>
	);
}

export default Modify;
