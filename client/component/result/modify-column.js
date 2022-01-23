function getIntegerReplacement( context, replacement ) {
	if ( replacement.label ) {
		return replacement.label;
	}

	return parseInt( replacement.value, 10 );
}

function getReplaceValue( type, existing, replacement ) {
	if ( type === 'delete' ) {
		return {
			...existing,
			type: 'delete',
		};
	}

	if ( type === 'replace' ) {
		return {
			type: 'replace',
			value: existing.context || existing.value,
			value_label: existing.context || existing.value,
			replacement,
			replacement_label: replacement,
		};
	}

	return existing;
}

function getReplacedKeyvalue( context, replacement ) {
	return {
		key: getReplaceValue( replacement.type, context.key, replacement.key ),
		value: getReplaceValue( replacement.type_value, context.value, replacement.value ),
	};
}

function replaceListColumn( context, replacement, schema ) {
	if (
		schema.type === 'integer' &&
		! isNaN( parseInt( replacement.value, 10 ) ) &&
		parseInt( context.value, 10 ) !== parseInt( replacement.value, 10 )
	) {
		return {
			type: 'replace',
			replacement_value: parseInt( replacement.value, 10 ),
			replacement_label: getIntegerReplacement( context, replacement ),
		};
	}

	if (
		schema.type === 'member' &&
		replacement.values &&
		replacement.values[ 0 ] !== context.value &&
		schema.options !== 'api'
	) {
		const option = schema.options.find( ( item ) => item.value === replacement.values[ 0 ] );

		return {
			type: 'replace',
			replacement_value: replacement.values[ 0 ],
			replacement_label: option ? option.label : replacement.values[ 0 ],
		};
	}

	if ( schema.type === 'date' && replacement.value ) {
		return {
			type: 'replace',
			replacement_value: replacement.value.toDateString() + ' ' + replacement.value.toLocaleTimeString(),
			replacement_label: replacement.value.toDateString() + ' ' + replacement.value.toLocaleTimeString(),
		};
	}

	if ( schema.type === 'string' && replacement.replaceValue !== undefined ) {
		// If replacing many string contexts then we want to merge them all together
		if ( context.type === 'string' && ! replacement.matchesOnly ) {
			return {};
		}

		if (
			replacement.originalValue &&
			replacement.originalValue.replace( /\r\n/g, '\n' ).trim() ===
				replacement.replaceValue.replace( /\r\n/g, '\n' ).trim()
		) {
			return {};
		}

		if ( context.value === replacement.replaceValue ) {
			return {};
		}

		if ( replacement.matchesOnly ) {
			return {
				...context,
				matches: context.matches.map( ( match ) => ( {
					...match,
					replacement: replacement.replaceValue,
				} ) ),
			};
		}

		if ( replacement.replaceValue === '' ) {
			return {
				type: 'delete',
			};
		}

		return {
			...context,
			type: 'replace',
			replacement: replacement.replaceValue,
			replacement_label: replacement.replaceValue,
		};
	}

	return {};
}

function replaceListContext( contexts, replacement, schema ) {
	if ( schema.type === 'member' && schema.options === 'api' ) {
		// Go through contexts and see if it exists in the replacement
		const removed = contexts
			.filter( ( item ) => item.type !== 'empty' )
			.filter( ( context ) => replacement.values.indexOf( context.value ) === -1 );
		const existing = contexts
			.filter( ( context ) => replacement.values.indexOf( context.value ) !== -1 )
			.filter( ( item ) => item.type !== 'empty' );
		const added = replacement.values
			.map( ( item, pos ) => {
				const found = existing.find( ( context ) => context.value === item );
				if ( ! found ) {
					return {
						type: 'add',
						value: item,
						value_label: ( replacement.label && replacement.label[ pos ] ) || item,
					};
				}

				return false;
			} )
			.filter( Boolean );

		return existing
			.concat(
				removed.map( ( item ) => ( {
					type: 'delete',
					value: item.value,
					value_label: item.value_label,
				} ) )
			)
			.concat( added )
			.map( ( context, pos ) => ( {
				...context,
				context_id: pos,
			} ) );
	}

	if ( schema.type === 'keyvalue' && replacement.items ) {
		const value = contexts
			.map( ( context, pos ) => ( {
				...context,
				...( replacement.items.length === 0 ? {} : getReplacedKeyvalue( context, replacement.items[ pos ] ) ),
			} ) )
			.concat(
				replacement.items.slice( contexts.length ).map( ( item, pos ) => ( {
					type: 'keyvalue',
					context_id: contexts.length + pos,
					key: {
						type: 'add',
						value: item.key,
						value_label: item.key,
					},
					value: {
						type: 'add',
						value: item.value,
						value_label: item.value,
					},
				} ) )
			);

		return value;
	}

	if (
		schema.type === 'string' &&
		! replacement.matchesOnly &&
		contexts.length > 0 &&
		contexts[ 0 ].type === 'string'
	) {
		const value = replacement.originalValue ? replacement.originalValue : contexts[ 0 ].context;
		const newContext = {
			type: 'value',
			value,
			value_label: value,
			context_id: contexts.length,
			hasMultiple: true,
			value_type: contexts[ 0 ].value_type,
			value_length: value.length + 1, // Force it to be loaded
		};

		// When replacing a whole string we convert it to a 'value', so that all the multiple contexts get merged together
		return [
			{
				...newContext,
				...replaceListColumn( newContext, replacement, schema ),
			},
		];
	}

	return contexts;
}

export function getReplacedColumn( column, replacement, columnSchema ) {
	if ( ! replacement ) {
		return column;
	}

	const newContexts = replaceListContext(
		column.contexts.map( ( context ) => ( {
			...context,
			...replaceListColumn( context, replacement, columnSchema ),
		} ) ),
		replacement,
		columnSchema
	);

	return {
		...column,
		context_count: newContexts.length,
		contexts: newContexts,
	};
}

function isReplaceKeyvalue( item ) {
	if ( item.type !== 'value' || item.type_value !== 'value' ) {
		return true;
	}

	return false;
}

export function hasValue( replacement, column, columnSchema ) {
	if ( replacement === null || replacement.column !== column.column_id ) {
		return false;
	}

	if ( columnSchema.type === 'integer' ) {
		return (
			! isNaN( parseInt( replacement.value, 10 ) ) &&
			parseInt( column.contexts[ 0 ].value, 10 ) !== parseInt( replacement.value, 10 )
		);
	}

	if ( columnSchema.type === 'member' ) {
		return replacement.values && replacement.values[ 0 ] !== column.contexts[ 0 ].value;
	}

	if ( columnSchema.type === 'keyvalue' && replacement.items ) {
		return replacement.items.find( ( item ) => isReplaceKeyvalue( item ) );
	}

	if ( columnSchema.type === 'string' ) {
		if ( column.contexts.length === 0 ) {
			return replacement.replaceValue !== undefined && replacement.replaceValue !== '';
		}

		if ( replacement.originalValue && replacement.originalValue === replacement.replaceValue ) {
			return false;
		}

		return (
			replacement.replaceValue !== undefined &&
			replacement.replaceValue !== ( column.contexts[ 0 ].value || column.contexts[ 0 ].search )
		);
	}

	if ( columnSchema.type === 'date' ) {
		return replacement.value !== undefined;
	}

	return false;
}
