<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Source;
use SearchRegex\Modifier;
use SearchRegex\Context;

/**
 * Modifier for member columns
 */
class Member_Value extends Modifier\Modifier {
	/**
	 * Array of values
	 *
	 * @var array<string|integer>
	 */
	private $values = [];

	public function __construct( array $option, Schema\Column $schema ) {
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
	 * @param Source\Source $source Source.
	 * @param array         $values Values.
	 * @param callable      $cb Functionc allback.
	 * @return list<object>
	 */
	private function get_contexts( Source\Source $source, array $values, $cb ) {
		$contexts = [];

		foreach ( $values as $row_value ) {
			/** @psalm-suppress UndefinedClass */
			$contexts[] = new $cb( $row_value, $source->convert_result_value( $this->schema, $row_value ) );
		}

		return $contexts;
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
		$same = array_intersect( $action_values, $column_values );
		$delete = array_diff( $column_values, $action_values );

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
		return array_diff( $action_values, $column_values );
	}

	public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode ) {
		$action_values = $this->values;
		$column_values = array_map(
			function( $context ) {
				return $this->get_value( $context instanceof Context\Type\Value ? $context->get_value() : '' );
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
			$same = array_intersect( $action_values, $column_values );
			$delete = array_diff( $column_values, $action_values );
			$add = array_diff( $action_values, $column_values );

			// Which one of the 'deletes' can 'replace' an 'add'
			foreach ( $add as $pos => $value ) {
				if ( count( $delete ) > 0 ) {
					$context = new Context\Type\Replace( $delete[0], $source->convert_result_value( $this->schema, (string) $delete[0] ) );
					$context->set_replacement( (string) $value, $source->convert_result_value( $this->schema, (string) $value ) );
					$updated[] = $context;

					unset( $add[ $pos ] );
					unset( $delete[0] );
					$delete = array_values( $delete );
				}
			}

			$add = array_values( $add );
		} elseif ( $this->operation === 'exclude' ) {
			list( $same, $delete ) = $this->get_exclude( $action_values, $column_values );
		} elseif ( $this->operation === 'include' ) {
			$add = $this->get_include( $action_values, $column_values );
		}

		// Replace existing contexts with the new ones
		$contexts = array_merge(
			$this->get_contexts( $source, $same, '\SearchRegex\Context\Type\Value' ),
			$this->get_contexts( $source, $add, '\SearchRegex\Context\Type\Add' ),
			$this->get_contexts( $source, $delete, '\SearchRegex\Context\Type\Delete' ),
			$updated
		);

		$column->set_contexts( $contexts );
		return $column;
	}
}
