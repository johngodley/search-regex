/**
 * External dependencies
 */

import { useSelector } from 'react-redux';
import React, { useEffect, useState } from 'react';
import { translate as __ } from 'i18n-calypso';
import classnames from 'classnames';

/**
 * Internal dependencies
 */

import { Select } from 'wp-plugin-components';
import { isLocked, hasTags, hasActionTag } from 'state/preset/selector';
import ModifyColumns from './modify-columns';
import Modify from 'component/schema/modify';
import Replace from 'component/replace';
import { getActions, getExportOptions } from './constants';
import { getSearchOptionsForSources } from 'state/search/selector';
import { getSchemaSourceColumn } from 'state/search/selector';
import './style.scss';

function getPresetAction( preset, pos ) {
	if ( preset ) {
		return preset.search.actionOption[ pos ];
	}

	return {};
}

function Actions( props ) {
	const { locked, tags, preset, headerClass, searchPhrase, disabled, sources, onSetSearch, search } = props;
	const { schema } = useSelector( ( state ) => state.search );
	const { action = '', actionOption = {}, replacement } = search;
	const actions = getActions( searchPhrase && searchPhrase.length > 0, sources.length === 1 );
	const currentAction = actions.find( ( item ) => item.value === action ) || actions[ 0 ];
	const [ currentSource, setSource ] = useState( sources.length > 0 ? sources[ 0 ] : '' );
	const filterOptions = getSearchOptionsForSources( sources, schema );

	useEffect(() => {
		if ( currentAction.disabled ) {
			onSetSearch( { action: actions[ 0 ].value, actionOption: [] } );
		}

		setSource( sources.length > 0 ? sources[ 0 ] : '' );
	}, [ sources ]);

	function removeModify( column ) {
		onSetSearch( {
			actionOption: actionOption.filter( ( option ) => option.column !== column.column ),
		} );
	}

	function onChange( pos, newValue, newLabel ) {
		onSetSearch( {
			actionOption: [
				...actionOption.slice( 0, pos ),
				{
					...actionOption[ pos ],
					...newValue,
					...( newLabel ? { label: newLabel } : {} ),
				},
				...actionOption.slice( pos + 1 ),
			],
		} );
	}

	return (
		<>
			{ ! isLocked( locked, 'replacement' ) && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className={ classnames( 'searchregex-search__action', headerClass ) }>
					<th>{ __( 'Action' ) }</th>
					<td>
						<Select
							items={ actions }
							name="action"
							value={ action }
							disabled={ disabled }
							className="searchregex-search__action__type"
							onChange={ ( ev ) => onSetSearch( { action: ev.target.value, actionOption: [] } ) }
						/>

						{ action === 'modify' && (
							<>
								<span>{ __( 'Source' ) }</span>
								<Select
									disabled={ disabled }
									name="actionSource"
									value={ currentSource }
									onChange={ ( ev ) => setSource( ev.target.value ) }
									items={ filterOptions }
								/>

								<ModifyColumns
									columns={ actionOption }
									disabled={ disabled }
									source={ currentSource }
									onSetSearch={ onSetSearch }
								/>
							</>
						) }

						{ action === 'export' && (
							<>
								<span>{ __( 'Export Format' ) }</span>

								<Select
									items={ getExportOptions() }
									name="export"
									value={ actionOption.format || 'json' }
									disabled={ disabled }
									onChange={ ( ev ) =>
										onSetSearch( { actionOption: { format: ev.target.value } } )
									}
								/>
							</>
						) }

						{ action === 'action' && (
							<>
								<span>{ __( 'WordPress Action' ) }</span>
								<input
									type="text"
									className=""
									value={ actionOption.hook || '' }
									onChange={ ( ev ) =>
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
							<label>
								<input
									type="checkbox"
									checked={ actionOption.selectedOnly || false }
									onChange={ ( ev ) =>
										onSetSearch( {
											actionOption: { ...actionOption, selectedOnly: ev.target.checked },
										} )
									}
								/>
								{ __( 'Only include selected columns' ) }
							</label>
						</p>
					</td>
				</tr>
			) }

			{ action === 'modify' && actionOption.length > 0 && (
				<tr>
					<th />
					<td>
						{ actionOption
							.filter( ( item, pos ) => ! hasActionTag( tags, getPresetAction( preset, pos ) ) )
							.map( ( column, pos ) => (
								<Modify
									column={ column }
									disabled={ disabled }
									key={ pos + '-' + column.column }
									schema={ getSchemaSourceColumn( schema, column.source, column.column ) }
									onRemove={ () => removeModify( column ) }
									onChange={ ( newValue, newLabel ) => onChange( pos, newValue, newLabel ) }
								/>
							) ) }
					</td>
				</tr>
			) }

			{ action === 'replace' && ! hasTags( tags, preset?.search?.replacement ?? '' ) && (
				<tr className="searchregex-search__replace">
					<th>{ __( 'Replace' ) }</th>
					<td>
						<Replace
							replacement={ replacement }
							locked={ locked }
							tags={ tags }
							preset={ preset }
							disabled={ disabled }
							setReplace={ onSetSearch }
						/>
					</td>
				</tr>
			) }
		</>
	);
}

export default Actions;
