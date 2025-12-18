import { useSelector } from 'react-redux';
import { useEffect, useState, ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Select } from '@wp-plugin-components';
import { isLocked, hasTags, hasActionTag } from '../../../state/preset/selector';
import ModifyColumns from './modify-columns';
import Modify from '../../../component/schema/modify';
import { getActions, getExportOptions } from './constants';
import { getSearchOptionsForSources, getSchemaSourceColumn } from '../../../state/search/selector';
import type { PresetValue, PresetTag } from '../../../types/preset';
import type { Schema, ModifyColumn } from '../../../types/search';
import './style.scss';

interface ActionOption {
	column?: string;
	source?: string;
	format?: string;
	selectedOnly?: boolean;
	hook?: string;
	label?: string;
	[ key: string ]: unknown;
}

interface SearchValues {
	action?: string;
	actionOption?: ActionOption | ActionOption[];
	replacement?: string | null;
}

interface RootState {
	search: {
		schema: Schema[];
	};
}

interface ActionsProps {
	locked: string[];
	tags: PresetTag[];
	preset: PresetValue | null;
	headerClass?: string;
	searchPhrase: string;
	disabled: boolean;
	sources: string[];
	onSetSearch: ( values: SearchValues ) => void;
	search: {
		action: string;
		actionOption: ActionOption | ActionOption[];
		replacement: string | null;
	};
}

function getPresetAction( preset: PresetValue | null, pos: number ): ActionOption {
	if ( preset && Array.isArray( preset.search.actionOption ) ) {
		return preset.search.actionOption[ pos ];
	}

	return {};
}

function Actions( props: ActionsProps ) {
	const { locked, tags, preset, headerClass, searchPhrase, disabled, sources, onSetSearch, search } = props;
	const { schema } = useSelector( ( state: RootState ) => state.search );
	const { action = '', actionOption = {}, replacement } = search;
	const actions = getActions( Boolean( searchPhrase && searchPhrase.length > 0 ), sources.length === 1 );
	const currentAction = actions.find( ( item ) => item.value === action ) || actions[ 0 ];
	const [ currentSource, setSource ] = useState( sources.length > 0 ? sources[ 0 ] : '' );
	const filterOptions = getSearchOptionsForSources( sources, schema );
	const exportCheckboxId = 'searchregex-export-selected';

	const actionOptionArray = Array.isArray( actionOption ) ? actionOption : [];
	const actionOptionObj = ! Array.isArray( actionOption ) ? actionOption : {};

	useEffect( () => {
		if ( currentAction.disabled ) {
			onSetSearch( { action: actions[ 0 ].value, actionOption: [] } );
		}

		setSource( sources.length > 0 ? sources[ 0 ] : '' );
	}, [ actions, currentAction.disabled, onSetSearch, sources ] );

	function removeModify( column: ActionOption ) {
		onSetSearch( {
			actionOption: actionOptionArray.filter( ( option ) => option.column !== column.column ),
		} );
	}

	function onChange( pos: number, newValue: ActionOption, newLabel?: string ) {
		onSetSearch( {
			actionOption: [
				...actionOptionArray.slice( 0, pos ),
				{
					...actionOptionArray[ pos ],
					...newValue,
					...( newLabel ? { label: newLabel } : {} ),
				},
				...actionOptionArray.slice( pos + 1 ),
			],
		} );
	}

	return (
		<>
			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__action', headerClass ) }>
					<th>{ __( 'Action', 'search-regex' ) }</th>
					<td>
						<Select
							items={ actions }
							name="action"
							value={ action }
							disabled={ disabled }
							className="searchregex-search__action__type"
							onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
								onSetSearch( { action: ev.target.value, actionOption: [] } )
							}
						/>

						{ action === 'modify' && (
							<>
								<span>{ __( 'Source', 'search-regex' ) }</span>
								<Select
									disabled={ disabled }
									name="actionSource"
									value={ currentSource }
									onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
										setSource( ev.target.value )
									}
									items={ filterOptions }
								/>

								<ModifyColumns
									columns={
										actionOptionArray as unknown as { column: string; [ key: string ]: unknown }[]
									}
									disabled={ disabled }
									source={ currentSource }
									onSetSearch={
										onSetSearch as ( values: {
											actionOption: { column: string; [ key: string ]: unknown }[];
										} ) => void
									}
								/>
							</>
						) }

						{ action === 'export' && (
							<>
								<span>{ __( 'Export Format', 'search-regex' ) }</span>

								<Select
									items={ getExportOptions() }
									name="export"
									value={ actionOptionObj.format || 'json' }
									disabled={ disabled }
									onChange={ ( ev: ChangeEvent< HTMLSelectElement > ) =>
										onSetSearch( { actionOption: { format: ev.target.value } } )
									}
								/>
							</>
						) }

						{ action === 'action' && (
							<>
								<span>{ __( 'WordPress Action', 'search-regex' ) }</span>
								<input
									type="text"
									className=""
									value={ actionOptionObj.hook || '' }
									onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
										onSetSearch( { actionOption: { hook: ev.target.value } } )
									}
									disabled={ disabled }
								/>
							</>
						) }

						<span>{ currentAction.desc }</span>
					</td>
				</tr>
			) }

			{ action === 'export' && (
				<tr className="searchregex-search__export">
					<th />
					<td>
						<p>
							<input
								id={ exportCheckboxId }
								type="checkbox"
								checked={ actionOptionObj.selectedOnly || false }
								onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
									onSetSearch( {
										actionOption: { ...actionOptionObj, selectedOnly: ev.target.checked },
									} )
								}
							/>
							<label htmlFor={ exportCheckboxId }>
								{ __( 'Only include selected columns', 'search-regex' ) }
							</label>
						</p>
					</td>
				</tr>
			) }

			{ action === 'modify' && actionOptionArray.length > 0 && (
				<tr>
					<th />
					<td>
						{ actionOptionArray
							.filter(
								( _item, pos ) =>
									! hasActionTag(
										tags,
										getPresetAction( preset, pos ) as {
											searchValue?: string;
											replaceValue?: string;
										}
									)
							)
							.map( ( column, pos ) => {
								const columnSchema = getSchemaSourceColumn(
									schema,
									column.source ?? '',
									column.column ?? ''
								);
								if ( ! columnSchema ) {
									return null;
								}
								return (
									<Modify
										column={ column as unknown as ModifyColumn }
										disabled={ disabled }
										key={ pos + '-' + column.column }
										schema={ columnSchema }
										onRemove={ () => removeModify( column ) }
										onChange={ ( newValue: Partial< ModifyColumn > ) =>
											onChange( pos, newValue as ActionOption, undefined )
										}
									/>
								);
							} ) }
					</td>
				</tr>
			) }

			{ action === 'replace' && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className="searchregex-search__replace">
					<th>{ __( 'Replace', 'search-regex' ) }</th>
					<td>
						<input
							type="text"
							value={ replacement ?? '' }
							onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
								onSetSearch( { replacement: ev.target.value } )
							}
							disabled={ disabled }
							placeholder={ __( 'Enter global replacement text', 'search-regex' ) }
						/>
					</td>
				</tr>
			) }
		</>
	);
}

export default Actions;
