import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { apiFetch } from '@wp-plugin-lib';
import { ApiUtils } from '../../../lib/api-utils';
import ModifyType from './types';
import type { SchemaColumn, ModifyColumn } from '../../../types/search';
import './style.scss';

interface ModifyProps {
	disabled: boolean;
	schema: SchemaColumn;
	column: ModifyColumn;
	onRemove?: () => void;
	onChange: ( values: Partial< ModifyColumn > ) => void;
}

export default function Modify( { disabled, schema, column, onRemove, onChange }: ModifyProps ): JSX.Element {
	function fetchData( value: string ): Promise< unknown > {
		return apiFetch( ApiUtils.source.complete( column.source || '', column.column, value ) );
	}

	return (
		<div className="searchregex-modify">
			<div className="searchregex-modify__name">
				<span>{ schema.title }</span>

				{ onRemove && (
					<button
						type="button"
						aria-label={ __( 'Remove modification', 'search-regex' ) }
						onClick={ onRemove }
						disabled={ disabled }
						className={ clsx( 'dashicons', 'dashicons-trash', disabled && 'dashicons__disabled' ) }
					/>
				) }
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
