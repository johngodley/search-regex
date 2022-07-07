/**
 * External dependencies
 */

import React from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { apiFetch } from '@wp-plugin-lib';
import { Select, Button, Badge } from '@wp-plugin-components';
import SearchRegexApi from '../../../lib/api-request';
import FilterType from './types';
import './style.scss';

const MAX_OR_FILTERS = 10;

function getOptionsForColumn( columns ) {
	return columns.map( ( column ) => ( { label: column.title, value: column.column } ) );
}

function FilterItem( { item, columns, disabled, onChange, schema, fetchData, onRemove } ) {
	if ( ! schema ) {
		return null;
	}

	return (
		<div className={ classnames( 'searchregex-filter__item', `searchregex-filter__type__${ schema.type }` ) }>
			<Select
				name="filter_type"
				items={ getOptionsForColumn( columns ) }
				value={ item.column }
				disabled={ disabled }
				onChange={ ( ev ) => onChange( { column: ev.target.value } ) }
			/>

			<span
				onClick={ disabled ? () => {} : onRemove }
				className={ classnames( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) }
			/>

			<FilterType
				schema={ schema }
				item={ item }
				disabled={ disabled }
				fetchData={ fetchData }
				onChange={ ( values ) => onChange( { ...item, ...values } ) }
			/>
		</div>
	);
}

function Filter( props ) {
	const { schema, items, disabled, onChange, onRemove, source } = props;

	if ( ! schema ) {
		return null;
	}

	function addFilter() {
		onChange( items.concat( [ { column: items[ items.length - 1 ].column } ] ) );
	}

	function removeItem( pos ) {
		if ( items.length === 1 ) {
			onRemove();
		} else {
			onChange( [ ...items.slice( 0, pos ), ...items.slice( pos + 1 ) ] );
		}
	}

	function fetchData( column, value ) {
		return apiFetch( SearchRegexApi.source.complete( source, column, value ) );
	}

	return (
		<div className="searchregex-filter__column">
			<div className="searchregex-filter__name">
				<span>{ schema.name }</span>

				<span
					onClick={ disabled ? () => {} : onRemove }
					className={ classnames( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) }
				/>
			</div>
			<div
				className={ classnames(
					'searchregex-filter__content',
					items.length > 1 && 'searchregex-filter__content__multiple'
				) }
			>
				{ items.map( ( item, columnPosition ) => (
					<React.Fragment key={ `${ schema.name }-${ item.column }-${ columnPosition }` }>
						<FilterItem
							item={ item }
							schema={ schema.columns.find( ( finding ) => item.column === finding.column ) }
							columns={ schema.columns }
							disabled={ disabled }
							fetchData={ ( value ) => fetchData( item.column, value ) }
							onChange={ ( filter ) =>
								onChange( [
									...items.slice( 0, columnPosition ),
									filter,
									...items.slice( columnPosition + 1 ),
								] )
							}
							onRemove={ () => removeItem( columnPosition ) }
						/>
						{ columnPosition !== items.length - 1 && (
							<Badge disabled={ disabled }>{ __( 'OR' ) }</Badge>
						) }
					</React.Fragment>
				) ) }
			</div>
			<div className="searchregex-filter__action">
				<Button disabled={ disabled || items.length === MAX_OR_FILTERS } onClick={ addFilter }>
					{ __( 'Add sub-filter (OR)' ) }
				</Button>
			</div>
		</div>
	);
}

export default Filter;
