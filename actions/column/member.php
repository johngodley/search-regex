<?php

namespace SearchRegex;

use SearchRegex\Match_Context_Value;
use SearchRegex\Match_Context_Add;
use SearchRegex\Match_Context_Delete;

class Action_Modify_Member extends Action_Modify_Column {
	private $values = [];

	public function __construct( $option, Schema_Column $schema ) {
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

	private function get_value( $value ) {
		if ( is_numeric( $value ) ) {
			return intval( $value, 10 );
		}

		return $value;
	}

	private function get_contexts( $source, array $values, $cb ) {
		$contexts = [];

		foreach ( $values as $pos => $row_value ) {
			$context = new $cb( $row_value, $source->convert_result_value( $this->schema, $row_value ) );
			$contexts[] = $context;
		}

		return $contexts;
	}

	private function get_replace_join( $action_values, $column_values ) {
		$add = array_filter( $action_values, function( $item ) use ( $column_values ) {
			return ! in_array( $item, $column_values );
		} );
		$delete = array_filter( $column_values, function( $item ) use ( $action_values ) {
			return ! in_array( $item, $action_values );
		} );
		$same = array_filter( $column_values, function( $item ) use ( $action_values ) {
			return in_array( $item, $action_values );
		} );

		return [ $delete, $add, $same ];
	}

	private function get_exclude( $action_values, $column_values ) {
		// Remove any that we're excluding
		$same = array_filter( $action_values, function( $item ) use ( $column_values ) {
			return ! in_array( $item, $column_values );
		} );
		$delete = array_filter( $action_values, function( $item ) use ( $column_values ) {
			return in_array( $item, $column_values );
		} );

		return [ $same, $delete ];
	}

	private function get_include( $action_values, $column_values ) {
		// Add any that we don't already have
		return array_filter( $action_values, function( $item ) use ( $column_values ) {
			return ! in_array( $item, $column_values );
		} );
	}

	public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw ) {
		$action_values = $this->values;
		$column_values = array_values( array_filter( array_map( function( $context ) {
			return $this->get_value( $context->get_value() );
		}, $column->get_contexts() ) ) );

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
					if ( ! in_array( $item, $column_values ) ) {
						$context = new Match_Context_Replace( $column_values[ $pos ], $source->convert_result_value( $this->schema, $column_values[ $pos ] ) );
						$context->set_replacement( $item, $source->convert_result_value( $this->schema, $item ) );
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
		$contexts = array_merge(
			$this->get_contexts( $source, $same, 'SearchRegex\Match_Context_Value' ),
			$this->get_contexts( $source, $add, 'SearchRegex\Match_Context_Add' ),
			$this->get_contexts( $source, $delete, 'SearchRegex\Match_Context_Delete' ),
			$updated,
		);

		$column->set_contexts( $contexts );
		return $column;
	}
}
