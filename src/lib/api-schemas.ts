import { z } from 'zod';

/**
 * Match schema (used in ContextText)
 */
const matchSchema = z.object( {
	context_offset: z.number(),
	pos_id: z.number(),
	match: z.string(),
	replacement: z.string(),
	captures: z.array( z.string() ),
} );

/**
 * Context schema - discriminated union based on type
 * Based on PHP Context classes:
 * - 'string' from Context\Type\Text
 * - 'value' from Context\Type\Value
 * - 'replace' from Context\Type\Replace
 * - 'add' from Context\Type\Add
 * - 'delete' from Context\Type\Delete
 * - 'keyvalue' from Context\Type\Pair
 * - 'empty' from Context\Type\Empty_Type
 *
 * Note: Using a union instead of discriminatedUnion to allow unknown types for debugging
 */
const contextSchema: z.ZodType< any > = z.union( [
	// ContextText - type 'string'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'string' ),
		context: z.string(),
		crop: z
			.object( {
				start: z.number().optional(),
				end: z.number().optional(),
			} )
			.optional(),
		search: z.string().optional(),
		flags: z.unknown().optional(),
		matches: z.array( matchSchema ),
		match_count: z.number(),
		value_type: z.string().optional(),
	} ),
	// ContextList - type 'value'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'value' ),
		value: z.string(),
		value_type: z.string(),
		value_label: z.string(),
		value_length: z.number(),
	} ),
	// ContextList - type 'replace'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'replace' ),
		value: z.string(),
		value_type: z.string(),
		value_label: z.string(),
		value_length: z.number(),
		replacement: z.string(),
		replacement_label: z.string(),
	} ),
	// ContextList - type 'add'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'add' ),
		value: z.string(),
		value_type: z.string(),
		value_label: z.string(),
		value_length: z.number(),
	} ),
	// ContextList - type 'delete'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'delete' ),
		value: z.string(),
		value_type: z.string(),
		value_label: z.string(),
		value_length: z.number(),
	} ),
	// ContextList - type 'keyvalue' (Pair)
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'keyvalue' ),
		key: z.any(), // Recursive - can be any context type
		value: z.any(), // Recursive - can be any context type
	} ),
	// ContextList - type 'empty'
	z.object( {
		context_id: z.union( [ z.string(), z.number() ] ),
		type: z.literal( 'empty' ),
	} ),
	// Fallback for unknown types - log them so we can fix the schema
	z
		.object( {
			context_id: z
				.union( [ z.string(), z.number() ] )
				.transform( ( val ) => String( val ) )
				.optional(),
			type: z.string(), // Accept any string type
		} )
		.passthrough(), // Allow any additional fields
] );

/**
 * Search API response schema
 */
export const searchResponseSchema = z.object( {
	results: z.array(
		z.object( {
			row_id: z.union( [ z.string(), z.number() ] ).transform( ( val ) => String( val ) ),
			match_count: z.number().optional(),
			source_name: z.string(),
			source_type: z.string(),
			title: z.string(),
			actions: z.unknown(),
			columns: z.array(
				z.object( {
					column_id: z.string(),
					column_label: z.string().optional(),
					contexts: z.array( contextSchema ),
					context_count: z.number().optional(),
					match_count: z.number().optional(),
				} )
			),
		} )
	),
	progress: z.object( {
		current: z.number().optional(),
		rows: z.number().optional(),
		next: z.union( [ z.boolean(), z.number() ] ),
		previous: z.union( [ z.boolean(), z.number() ] ).optional(),
	} ),
	totals: z.object( {
		matched_rows: z.number(),
		rows: z.number(),
		custom: z
			.array(
				z.object( {
					name: z.string(),
					value: z.number(),
				} )
			)
			.optional(),
	} ),
	status: z.string().optional(),
} );

export type SearchResponse = z.infer< typeof searchResponseSchema >;

/**
 * Delete row response schema
 */
export const deleteRowResponseSchema = z.object( {
	result: z.string(),
} );

export type DeleteRowResponse = z.infer< typeof deleteRowResponseSchema >;

/**
 * Load row response schema
 */
export const loadRowResponseSchema = z.object( {
	result: z.object( {
		row_id: z.string(),
		source_name: z.string(),
		source_type: z.string(),
		title: z.string(),
		actions: z.unknown(),
		columns: z.array(
			z.object( {
				column_id: z.string(),
				contexts: z.array( z.unknown() ),
			} )
		),
	} ),
} );

export type LoadRowResponse = z.infer< typeof loadRowResponseSchema >;

/**
 * Save row response schema
 * The result is a Result object (same structure as in searchResponseSchema)
 */
export const saveRowResponseSchema = z.object( {
	result: z.object( {
		row_id: z.union( [ z.string(), z.number() ] ).transform( ( val ) => String( val ) ),
		match_count: z.number().optional(),
		source_name: z.string(),
		source_type: z.string(),
		title: z.string(),
		actions: z.unknown(),
		columns: z.array(
			z.object( {
				column_id: z.string(),
				column_label: z.string().optional(),
				contexts: z.array( z.unknown() ),
				context_count: z.number().optional(),
				match_count: z.number().optional(),
			} )
		),
	} ),
	row: z
		.object( {
			row_id: z.string(),
			source_name: z.string(),
			source_type: z.string(),
			title: z.string(),
			actions: z.unknown(),
			columns: z.array(
				z.object( {
					column_id: z.string(),
					contexts: z.array( z.unknown() ),
				} )
			),
		} )
		.optional(),
} );

export type SaveRowResponse = z.infer< typeof saveRowResponseSchema >;

/**
 * Source complete response schema
 */
export const sourceCompleteResponseSchema = z.array(
	z.object( {
		value: z.string(),
		label: z.string(),
	} )
);

export type SourceCompleteResponse = z.infer< typeof sourceCompleteResponseSchema >;

/**
 * Preset tag schema
 */
export const presetTagSchema = z.object( {
	name: z.string(),
	label: z.string(),
} );

/**
 * Preset value schema (for API responses - id is required)
 */
export const presetValueSchema = z.object( {
	search: z.unknown(), // SearchValues - keeping as unknown for now
	id: z.string(),
	name: z.string(),
	description: z.string().optional(),
	locked: z.array( z.string() ),
	tags: z.array( presetTagSchema ),
} );

/**
 * Preset import schema (for imports - id is optional, will be generated)
 */
export const presetImportValueSchema = z.object( {
	search: z.unknown(), // SearchValues - keeping as unknown for now
	id: z.string().optional(), // Optional for imports (will be generated)
	name: z.string(),
	description: z.string().optional(),
	locked: z.array( z.string() ).optional().default( [] ),
	tags: z.array( presetTagSchema ).optional().default( [] ),
} );

/**
 * Preset import schema - accepts single preset or array of presets
 */
export const presetImportSchema = z
	.union( [ presetImportValueSchema, z.array( presetImportValueSchema ) ] )
	.transform( ( val ) => {
		// Always return as array for consistency
		return Array.isArray( val ) ? val : [ val ];
	} );

/**
 * Preset response schema
 */
export const presetResponseSchema = z.object( {
	presets: z.array( presetValueSchema ),
} );

export type PresetResponse = z.infer< typeof presetResponseSchema >;

/**
 * Preset upload response schema
 */
export const presetUploadResponseSchema = z.object( {
	presets: z.array( presetValueSchema ),
	imported: z.number(),
} );

export type PresetUploadResponse = z.infer< typeof presetUploadResponseSchema >;

/**
 * Settings values schema
 */
export const settingsValuesSchema = z.object( {
	support: z.boolean(),
	defaultPreset: z.string(), // Preset ID is a string (hex format)
	rest_api: z.number(),
	update_notice: z.union( [ z.string(), z.literal( false ) ] ).optional(),
} );

export type SettingsValues = z.infer< typeof settingsValuesSchema >;

/**
 * Settings API response schema
 */
export const settingsResponseSchema = z.object( {
	settings: settingsValuesSchema,
	warning: z.unknown().optional(),
} );

export type SettingsResponse = z.infer< typeof settingsResponseSchema >;

/**
 * Filter item schema (for validation)
 */
const filterItemSchema = z.object( {
	column: z.string(),
	logic: z.string().optional(),
	value: z.string().optional(),
	startValue: z.union( [ z.number(), z.string() ] ).optional(),
	endValue: z.union( [ z.number(), z.string() ] ).optional(),
	flags: z.array( z.string() ).optional(),
	values: z.array( z.string() ).optional(),
	key: z.string().optional(),
	keyLogic: z.string().optional(),
	keyFlags: z.array( z.string() ).optional(),
	valueLogic: z.string().optional(),
	valueFlags: z.array( z.string() ).optional(),
} );

/**
 * Filter schema (for validation)
 */
const filterSchema = z.object( {
	type: z.string(),
	items: z.array( filterItemSchema ),
} );

/**
 * Search values schema (for validation)
 * Note: This is a partial schema - some fields are optional and filters can be complex
 * This schema is used for validation but doesn't replace the SearchValues interface
 */
export const searchValuesSchema = z
	.object( {
		searchPhrase: z.string().optional(),
		searchFlags: z.array( z.string() ).optional(),
		source: z.array( z.string() ).optional(),
		replacement: z.string().optional(),
		perPage: z.number().optional(),
		filters: z.array( filterSchema ).optional(),
		view: z.array( z.string() ).optional(),
		action: z.string().optional(),
		actionOption: z.unknown().optional(),
	} )
	.passthrough(); // Allow additional properties

/**
 * Query parameter schema for URL parsing
 * URL params come as strings and need transformation
 */
export const queryParamsSchema = z
	.object( {
		searchphrase: z.string().optional(),
		searchflags: z
			.string()
			.optional()
			.transform( ( val ) => ( val ? val.split( ',' ).filter( Boolean ) : [] ) ),
		source: z
			.string()
			.optional()
			.transform( ( val ) => ( val ? val.split( ',' ).filter( Boolean ) : [] ) ),
		replacement: z.string().optional(),
		perpage: z
			.string()
			.optional()
			.transform( ( val ) => ( val ? parseInt( val, 10 ) : undefined ) )
			.pipe( z.number().optional() ),
		filters: z
			.string()
			.optional()
			.transform( ( val ) => {
				if ( ! val ) {
					return [];
				}
				try {
					const parsed = JSON.parse( val );
					return Array.isArray( parsed ) ? parsed : [];
				} catch {
					return [];
				}
			} ),
		view: z
			.string()
			.optional()
			.transform( ( val ) => ( val ? val.split( ',' ).filter( Boolean ) : [] ) ),
	} )
	.passthrough(); // Allow other query params
