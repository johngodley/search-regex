/**
 * External dependencies
 */

import React, { useEffect, useState } from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */

import { Placeholder, Button } from 'wp-plugin-components';
import SearchFlags from 'component/search-flags';
import getValueType from 'component/value-type';

function getType( item ) {
	if ( item.context ) {
		return item.context;
	}

	return item.value;
}

function MetaValue( props ) {
	const { disabled, item, onChange, onAdd, onDelete, type, original, isNew = false } = props;
	const [ flags, setFlags ] = useState( [] );
	const valueType = getValueType( item.value_type );

	return (
		<>
			<div className="searchregex-filter__keyvalue__item">
				<span>{ __( 'Meta Key' ) }</span>

				<input
					type="text"
					value={ item.key }
					disabled={ disabled || item.type === 'delete' }
					placeholder={ item.type === 'add' ? __( 'New meta key' ) : '' }
					onChange={ ( ev ) =>
						onChange( {
							key: ev.target.value,
							type: original && getType( original.key ) === ev.target.value ? 'value' : type,
						} )
					}
				/>

				{ type === 'add' && isNew && <Button onClick={ onAdd }>{ __( 'Add' ) }</Button> }
				{ type === 'add' && ! isNew && <Button onClick={ onDelete }>{ __( 'Delete' ) }</Button> }
				{ type !== 'add' && (
					<input
						type="checkbox"
						disabled={ disabled }
						onChange={ ( ev ) => onDelete( ev.target.checked ) }
						checked={ item.type !== 'delete' }
					/>
				) }
			</div>

			<div className="searchregex-filter__keyvalue__item searchregex-filter__keyvalue__item__value">
				<span>{ __( 'Meta Value' ) }</span>

				{ flags.indexOf( 'multi' ) === -1 ? (
					<input
						type="text"
						value={ item.value }
						disabled={ disabled || item.type === 'delete' }
						onChange={ ( ev ) =>
							onChange( {
								value: ev.target.value,
								type_value:
									original && getType( original.value ) === ev.target.value ? 'value' : type,
							} )
						}
						placeholder={ item.type === 'add' ? __( 'New meta value' ) : '' }
					/>
				) : (
					<textarea
						value={ item.value }
						disabled={ disabled || item.type === 'delete' }
						onChange={ ( ev ) =>
							onChange( {
								value: ev.target.value,
								type_value:
									original && getType( original.value ) === ev.target.value ? 'value' : type,
							} )
						}
						placeholder={ item.type === 'add' ? __( 'New meta value' ) : '' }
					/>
				) }

				<SearchFlags
					flags={ flags }
					disabled={ disabled || item.type === 'delete' }
					onChange={ setFlags }
					allowRegex={ false }
					allowMultiline
					allowCase={ false }
				/>
			</div>

			{ valueType && <div className="searchregex-list__encoded">{ valueType }</div> }
		</>
	);
}

function getNewItem() {
	return {
		type: 'add',
		key: '',
		value: '',
		type_value: 'add',
	};
}

/**
 * Display a column modification form
 * @param {object} props - Component props
 * @param {boolean} props.disabled - Disable the form
 * @param {import('state/search/type').SchemaColumn} props.schema
 * @param {import('state/search/type').ResultColumn} props.column
 * @param {import('state/search/type').SetReplace} props.setReplacement - Change the replacement
 * @param {object|null} props.replacement - Row replacement value
 **/
function ReplaceKeyValue( props ) {
	const { schema, replacement, disabled, setReplacement, column, loadColumn } = props;
	const [ loading, setLoading ] = useState( true );
	const [ newItem, setItem ] = useState( null );

	useEffect(() => {
		loadColumn().then( ( columnData ) => {
			if ( columnData ) {
				setReplacement( {
					items: columnData.items.map( ( item, pos ) => ( {
						type: 'value',
						key: item.key,
						value: item.value,
						value_type: item.value_type,
					} ) ),
				} );
				setItem( getNewItem( columnData.items.length ) );
			} else {
				setReplacement( {
					items: [],
				} );
			}

			setLoading( false );
		} );
	}, []);

	if ( loading ) {
		return <Placeholder />;
	}

	function changeValue( pos, value ) {
		setReplacement( {
			items: [
				...replacement.items.slice( 0, pos ),
				{
					...replacement.items[ pos ],
					...value,
				},
				...replacement.items.slice( pos + 1 ),
			],
		} );
	}

	function deleteValue( pos, onoff ) {
		if ( replacement.items[ pos ].type === 'add' ) {
			setReplacement( {
				items: [ ...replacement.items.slice( 0, pos ), ...replacement.items.slice( pos + 1 ) ],
			} );
		} else {
			// Set delete, reseting any other changes
			changeValue( pos, {
				key: column.contexts[ pos ].key.value,
				value: column.contexts[ pos ].value.value,
				type: onoff ? 'value' : 'delete',
				type_value: onoff ? 'value' : 'delete',
			} );
		}
	}

	function addNew() {
		setReplacement( {
			items: replacement.items.concat( newItem ),
		} );

		setItem( getNewItem( replacement.items.length + 1 ) );
	}

	return (
		<>
			{ replacement.items.map( ( item, pos ) => (
				<MetaValue
					key={ pos }
					disabled={ disabled }
					item={ item }
					onChange={ ( newValue ) => changeValue( pos, newValue ) }
					type={ item.type === 'add' ? 'add' : 'replace' }
					onDelete={ ( onOff ) => deleteValue( pos, onOff ) }
					original={ column.contexts[ pos ] }
				/>
			) ) }

			{ newItem && (
				<MetaValue
					disabled={ disabled }
					item={ newItem }
					type="add"
					isNew
					onChange={ ( value ) => setItem( { ...newItem, ...value } ) }
					onAdd={ addNew }
				/>
			) }
		</>
	);
}

export default ReplaceKeyValue;
