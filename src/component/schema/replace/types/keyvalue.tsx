import { useEffect, useState, type ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Placeholder, Button } from '@wp-plugin-components';
import SearchFlags from '../../../search-flags';
import getValueType from '../../../value-type';
import type { SchemaColumn, ResultColumn, ContextList } from '../../../../types/search';

interface MetaItem {
	type: 'value' | 'delete' | 'replace' | 'add';
	key: string;
	value: string;
	value_type?: string;
	type_value?: 'value' | 'delete' | 'replace' | 'add';
}

interface MetaValueProps {
	disabled: boolean;
	item: MetaItem;
	onChange: ( values: Partial< MetaItem > ) => void;
	onAdd?: () => void;
	onDelete: ( onOff?: boolean ) => void;
	type: 'replace' | 'add';
	original?: ContextList;
	isNew?: boolean;
}

function getType( item: { context?: string; value?: string } ): string {
	if ( item.context ) {
		return item.context;
	}

	return item.value || '';
}

function MetaValue( {
	disabled,
	item,
	onChange,
	onAdd,
	onDelete,
	type,
	original,
	isNew = false,
}: MetaValueProps ): JSX.Element {
	const [ flags, setFlags ] = useState< string[] >( [] );
	const valueType = getValueType( item.value_type || '' );

	return (
		<>
			<div className="searchregex-filter__keyvalue__item">
				<span>{ __( 'Meta Key', 'search-regex' ) }</span>

				<input
					type="text"
					value={ item.key }
					disabled={ disabled || item.type === 'delete' }
					placeholder={ item.type === 'add' ? __( 'New meta key', 'search-regex' ) : '' }
					onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
						onChange( {
							key: ev.target.value,
							type:
								original &&
								getType( original.key as { context?: string; value?: string } ) === ev.target.value
									? 'value'
									: type,
						} )
					}
				/>

				{ type === 'add' && isNew && <Button onClick={ onAdd }>{ __( 'Add', 'search-regex' ) }</Button> }
				{ type === 'add' && ! isNew && (
					<Button onClick={ () => onDelete() }>{ __( 'Delete', 'search-regex' ) }</Button>
				) }
				{ type !== 'add' && (
					<input
						type="checkbox"
						disabled={ disabled }
						onChange={ ( ev: ChangeEvent< HTMLInputElement > ) => onDelete( ev.target.checked ) }
						checked={ item.type !== 'delete' }
					/>
				) }
			</div>

			<div className="searchregex-filter__keyvalue__item searchregex-filter__keyvalue__item__value">
				<span>{ __( 'Meta Value', 'search-regex' ) }</span>

				{ flags.indexOf( 'multi' ) === -1 ? (
					<input
						type="text"
						value={ item.value }
						disabled={ disabled || item.type === 'delete' }
						onChange={ ( ev: ChangeEvent< HTMLInputElement > ) =>
							onChange( {
								value: ev.target.value,
								type_value:
									original &&
									getType( original.value as { context?: string; value?: string } ) ===
										ev.target.value
										? 'value'
										: type,
							} )
						}
						placeholder={ item.type === 'add' ? __( 'New meta value', 'search-regex' ) : '' }
					/>
				) : (
					<textarea
						value={ item.value }
						disabled={ disabled || item.type === 'delete' }
						onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) =>
							onChange( {
								value: ev.target.value,
								type_value:
									original &&
									getType( original.value as { context?: string; value?: string } ) ===
										ev.target.value
										? 'value'
										: type,
							} )
						}
						placeholder={ item.type === 'add' ? __( 'New meta value', 'search-regex' ) : '' }
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

function getNewItem(): MetaItem {
	return {
		type: 'add',
		key: '',
		value: '',
		type_value: 'add',
	};
}

interface ReplaceKeyValueProps {
	schema: SchemaColumn;
	replacement: { items: MetaItem[] };
	disabled: boolean;
	setReplacement: ( values: { items: MetaItem[] } ) => void;
	column: ResultColumn;
	loadColumn: () => Promise< { items: Array< { key: string; value: string; value_type?: string } > } >;
	fetchData: ( value: string ) => Promise< unknown >;
	context: unknown;
}

export default function ReplaceKeyValue( {
	schema,
	replacement,
	disabled,
	setReplacement,
	column,
	loadColumn,
}: ReplaceKeyValueProps ): JSX.Element {
	const [ loading, setLoading ] = useState( true );
	const [ newItem, setItem ] = useState< MetaItem | null >( null );
	void schema;

	useEffect( () => {
		loadColumn().then( ( columnData ) => {
			if ( columnData ) {
				setReplacement( {
					items: columnData.items.map( ( item ) => {
						const metaItem: MetaItem = {
							type: 'value',
							key: item.key,
							value: item.value,
						};
						if ( item.value_type !== undefined ) {
							metaItem.value_type = item.value_type;
						}
						return metaItem;
					} ),
				} );
				setItem( getNewItem() );
			} else {
				setReplacement( {
					items: [],
				} );
			}

			setLoading( false );
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	if ( loading ) {
		return <Placeholder />;
	}

	function changeValue( pos: number, value: Partial< MetaItem > ): void {
		const existingItem = replacement.items[ pos ];
		if ( ! existingItem ) {
			return;
		}

		const updatedItem: MetaItem = { ...existingItem };
		if ( value.type !== undefined ) {
			updatedItem.type = value.type;
		}
		if ( value.key !== undefined ) {
			updatedItem.key = value.key;
		}
		if ( value.value !== undefined ) {
			updatedItem.value = value.value;
		}
		if ( value.value_type !== undefined ) {
			updatedItem.value_type = value.value_type;
		}
		if ( value.type_value !== undefined ) {
			updatedItem.type_value = value.type_value;
		}

		setReplacement( {
			items: [ ...replacement.items.slice( 0, pos ), updatedItem, ...replacement.items.slice( pos + 1 ) ],
		} );
	}

	function deleteValue( pos: number, onoff?: boolean ): void {
		const item = replacement.items[ pos ];
		if ( ! item ) {
			return;
		}

		if ( item.type === 'add' ) {
			setReplacement( {
				items: [ ...replacement.items.slice( 0, pos ), ...replacement.items.slice( pos + 1 ) ],
			} );
		} else {
			const contexts = column.contexts as ContextList[];
			const context = contexts[ pos ];
			if ( context ) {
				changeValue( pos, {
					key: ( context.key as { value: string } ).value,
					value: ( context.value as unknown as { value: string } ).value,
					type: onoff ? 'value' : 'delete',
					type_value: onoff ? 'value' : 'delete',
				} );
			}
		}
	}

	function addNew(): void {
		if ( newItem ) {
			setReplacement( {
				items: [ ...replacement.items, newItem ],
			} );

			setItem( getNewItem() );
		}
	}

	return (
		<>
			{ replacement.items.map( ( item, pos ) => {
				const original = ( column.contexts as ContextList[] )[ pos ];
				return (
					<MetaValue
						key={ pos }
						disabled={ disabled }
						item={ item }
						onChange={ ( newValue ) => changeValue( pos, newValue ) }
						type={ item.type === 'add' ? 'add' : 'replace' }
						onDelete={ ( onOff ) => deleteValue( pos, onOff ) }
						{ ...( original !== undefined && { original } ) }
					/>
				);
			} ) }

			{ newItem && (
				<MetaValue
					disabled={ disabled }
					item={ newItem }
					type="add"
					isNew
					onChange={ ( value ) => setItem( { ...newItem, ...value } ) }
					onAdd={ addNew }
					onDelete={ () => {} }
				/>
			) }
		</>
	);
}
