<?php

namespace SearchRegex;

use SearchRegex\Match_Context_Value;
use SearchRegex\Match_Context_Add;
use SearchRegex\Match_Context_Delete;

/**
 * Modifier for member columns
 */
class Modify_Member extends Modifier {
	/**
	 * Array of values
	 *
	 * @var array<string|integer>
	 */
	private $values = [];

	public function __construct( array $option, Schema_Column $schema ) {
		parent::__construct( $option, $schema );

		$this->operation = 'replace';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'replace', 'include', 'exclude' ], true ) ) {
			$this->operation = $option['operation'];
		}

		if ( $schema->get_options() ) {
			$this->values = [ $schema->get_options()[0]['value'] ];
		}

		if ( isset( $option['values'] ) && is_array( $option['values'] ) ) {
			$this->values = array_map( [ $this, 'get_value' ], $option['values'] );
		}
	}

	public function to_json() {
		return array_merge(
			parent::to_json(),
			[
				'operation' => $this->operation,
				'values' => $this->values,
			]
		);
	}

	/**
	 * Determine if it's a numeric of string value and get the appropriate value.
	 *
	 * @param string|integer $value Value.
	 * @return string|integer
	 */
	private function get_value( $value ) {
		if ( is_numeric( $value ) ) {
			return intval( $value, 10 );
		}

		return $value;
	}

	/**
	 * Get the Match_Contexs for the values
	 *
	 * @param Search_Source $source Source.
	 * @param array         $values Values.
	 * @param callable      $cb Functionc allback.
	 * @return list<object>
	 */
	private function get_contexts( Search_Source $source, array $values, $cb ) {
		$contexts = [];

		foreach ( $values as $row_value ) {
			/** @psalm-suppress UndefinedClass */
			$contexts[] = new $cb( $row_value, $source->convert_result_value( $this->schema, $row_value ) );
		}

		return $contexts;
	}

	/**
	 * Get the deleted, added, and same contexts when performing a replace
	 *
	 * @param array<string|integer> $action_values Values to modify.
	 * @param array<string|integer> $column_values Values in the column.
	 * @return array
	 */
	private function get_replace_join( $action_values, $column_values ) {
		$add = array_filter( $action_values, function( $item ) use ( $column_values ) {
			// phpcs:ignore
			return ! in_array( $item, $column_values );
		} );
		$delete = array_filter( $column_values, function( $item ) use ( $action_values ) {
			// phpcs:ignore
			return ! in_array( $item, $action_values );
		} );
		$same = array_filter( $column_values, function( $item ) use ( $action_values ) {
			// phpcs:ignore
			return in_array( $item, $action_values );
		} );

		return [ $delete, $add, $same ];
	}

	/**
	 * Get all values that we are not matching
	 *
	 * @param array<string|integer> $action_values Values to check for.
	 * @param array<string|integer> $column_values Values to check against.
	 * @return array
	 */
	private function get_exclude( $action_values, $column_values ) {
		// Remove any that we're excluding
		$same = array_filter( $action_values, function( $item ) use ( $column_values ) {
			// phpcs:ignore
			return ! in_array( $item, $column_values );
		} );
		$delete = array_filter( $action_values, function( $item ) use ( $column_values ) {
			// phpcs:ignore
			return in_array( $item, $column_values );
		} );

		return [ $same, $delete ];
	}

	/**
	 * Get all values that we are  matching
	 *
	 * @param array<string|integer> $action_values Values to check for.
	 * @param array<string|integer> $column_values Values to check against.
	 * @return array<string|integer>
	 */
	private function get_include( $action_values, $column_values ) {
		// Add any that we don't already have
		return array_filter( $action_values, function( $item ) use ( $column_values ) {
			// phpcs:ignore
			return ! in_array( $item, $column_values );
		} );
	}

	public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw ) {
		$action_values = $this->values;
		$column_values = array_map(
			function( $context ) {
				return $this->get_value( $context instanceof Match_Context_Value ? $context->get_value() : '' );
			},
			$column->get_contexts()
		);
		$column_values = array_values( array_filter( $column_values ) );

		// Now create a set of arrays for members that are the same, added, deleted, and updated
		$same = $column_values;
		$add = [];
		$delete = [];
		$updated = [];

		if ( $this->operation === 'replace' ) {
			if ( $this->schema->get_join_column() ) {
				list( $delete, $add, $same ) = $this->get_replace_join( $action_values, $column_values );
			} else {
				$same = [];

				// Create a context for each one
				foreach ( $action_values as $pos => $item ) {
					// phpcs:ignore
					if ( ! in_array( $item, $column_values ) ) {
						$context = new Match_Context_Replace( $column_values[ $pos ], $source->convert_result_value( $this->schema, (string) $column_values[ $pos ] ) );
						$context->set_replacement( (string) $item, $source->convert_result_value( $this->schema, (string) $item ) );
						$updated[] = $context;
					}
				}
			}
		} elseif ( $this->operation === 'exclude' ) {
			list( $same, $delete ) = $this->get_exclude( $action_values, $column_values );
		} elseif ( $this->operation === 'include' ) {
			$add = $this->get_include( $action_values, $column_values );
		}

		// Replace existing contexts with the new ones
		/** @psalm-suppress all */
		$contexts = array_merge(
			$this->get_contexts( $source, $same, 'SearchRegex\Match_Context_Value' ),
			$this->get_contexts( $source, $add, 'SearchRegex\Match_Context_Add' ),
			$this->get_contexts( $source, $delete, 'SearchRegex\Match_Context_Delete' ),
			$updated,
		);

		/** @psalm-suppress ArgumentTypeCoercion */
		$column->set_contexts( $contexts );
		return $column;
	}
}
