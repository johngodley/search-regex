import { type ChangeEvent, Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { apiFetch } from '@wp-plugin-lib';
import { Select, Button, Badge } from '@wp-plugin-components';
import { ApiUtils } from '../../../lib/api-utils';
import FilterType from './types';
import type { SelectOption, SchemaColumn, FilterItem as FilterItemType, Schema } from '../../../types/search';
import './style.scss';

const MAX_OR_FILTERS = 10;

function getOptionsForColumn( columns: SchemaColumn[] ): SelectOption[] {
	return columns
		.filter( ( column ) => column.column && column.title )
		.map( ( column ) => ( { label: column.title as string, value: column.column as string } ) );
}

interface FilterItemProps {
	item: FilterItemType;
	columns: SchemaColumn[];
	disabled: boolean;
	onChange: ( values: Partial< FilterItemType > ) => void;
	schema: SchemaColumn | undefined;
	fetchData: ( value: string ) => Promise< unknown >;
	onRemove: () => void;
}

function FilterItem( {
	item,
	columns,
	disabled,
	onChange,
	schema,
	fetchData,
	onRemove,
}: FilterItemProps ): JSX.Element | null {
	if ( ! schema ) {
		return null;
	}

	function handleColumnChange( newColumn: string ): void {
		// When column changes, reset the filter to only have the column property
		// This prevents properties from the old column type (e.g., member) from
		// persisting when switching to a new column type (e.g., string)
		onChange( { column: newColumn } );
	}

	return (
		<div className={ clsx( 'searchregex-filter__item', `searchregex-filter__type__${ schema.type }` ) }>
			<Select
				name="filter_type"
				items={ getOptionsForColumn( columns ) }
				value={ item.column }
				disabled={ disabled }
				onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) => handleColumnChange( ev.target.value ) }
			/>

			<button
				type="button"
				aria-label={ __( 'Remove filter', 'search-regex' ) }
				onClick={ onRemove }
				disabled={ disabled }
				className={ clsx( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) }
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

interface FilterProps {
	schema: Schema | undefined;
	items: FilterItemType[];
	disabled: boolean;
	onChange: ( items: FilterItemType[] ) => void;
	onRemove: () => void;
	source: string;
}

function Filter( { schema, items, disabled, onChange, onRemove, source }: FilterProps ): JSX.Element | null {
	if ( ! schema ) {
		return null;
	}

	function addFilter(): void {
		const lastItem = items[ items.length - 1 ];
		if ( lastItem ) {
			onChange( [ ...items, { column: lastItem.column } ] );
		}
	}

	function removeItem( pos: number ): void {
		if ( items.length === 1 ) {
			onRemove();
		} else {
			onChange( [ ...items.slice( 0, pos ), ...items.slice( pos + 1 ) ] );
		}
	}

	function fetchData( column: string, value: string ): Promise< unknown > {
		return apiFetch( ApiUtils.source.complete( source, column, value ) );
	}

	return (
		<div className="searchregex-filter__column">
			<div className="searchregex-filter__name">
				<span>{ schema.name }</span>

				<button
					type="button"
					aria-label={ __( 'Remove filter group', 'search-regex' ) }
					onClick={ onRemove }
					disabled={ disabled }
					className={ clsx( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) }
				/>
			</div>
			<div
				className={ clsx(
					'searchregex-filter__content',
					items.length > 1 && 'searchregex-filter__content__multiple'
				) }
			>
				{ items.map( ( item, columnPosition ) => (
					<Fragment key={ `${ schema.name }-${ item.column }-${ columnPosition }` }>
						<FilterItem
							item={ item }
							schema={ schema.columns.find( ( finding ) => item.column === finding.column ) }
							columns={ schema.columns }
							disabled={ disabled }
							fetchData={ ( value ) => fetchData( item.column, value ) }
							onChange={ ( filter ) => {
								// If only 'column' property is provided, replace the entire item
								// (this happens when changing column type to avoid property pollution)
								// Otherwise, merge the changes with existing item
								const isColumnChange = Object.keys( filter ).length === 1 && 'column' in filter;
								const updatedItem: FilterItemType = isColumnChange
									? ( filter as FilterItemType )
									: { ...item, ...filter };

								onChange( [
									...items.slice( 0, columnPosition ),
									updatedItem,
									...items.slice( columnPosition + 1 ),
								] );
							} }
							onRemove={ () => removeItem( columnPosition ) }
						/>
						{ columnPosition !== items.length - 1 && (
							<Badge disabled={ disabled }>{ __( 'OR', 'search-regex' ) }</Badge>
						) }
					</Fragment>
				) ) }
			</div>
			<div className="searchregex-filter__action">
				<Button disabled={ disabled || items.length === MAX_OR_FILTERS } onClick={ addFilter }>
					{ __( 'Add sub-filter (OR)', 'search-regex' ) }
				</Button>
			</div>
		</div>
	);
}

export default Filter;
