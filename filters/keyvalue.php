<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Join;
use SearchRegex\Sql\Sql_Where_String;
use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_And;
use SearchRegex\Sql\Sql_Select_Column;

class Search_Filter_Keyvalue extends Search_Filter_Item {
	private $key_logic = 'any';
	private $value_logic = 'any';

	private $key = null;
	private $key_flags = null;
	private $value = null;
	private $value_flags;

	private $join_key = null;
	private $join_value = null;

	public function __construct( array $item, Schema_Column $schema ) {
		parent::__construct( $item, $schema );

		if ( ! empty( $item['key'] ) ) {
			$this->key = $item['key'];
		}

		if ( ! empty( $item['value'] ) ) {
			$this->value = $item['value'];
		}

		if ( isset( $item['keyLogic'] ) && in_array( strtolower( $item['keyLogic'] ), Search_Filter_String::LOGIC, true ) ) {
			$this->key_logic = strtolower( $item['keyLogic'] );
		}

		if ( isset( $item['valueLogic'] ) && in_array( strtolower( $item['valueLogic'] ), Search_Filter_String::LOGIC, true ) ) {
			$this->value_logic = strtolower( $item['valueLogic'] );
		}

		if ( isset( $item['keyLogic'] ) && $item['keyLogic'] === 'any' ) {
			$this->key = null;
		}

		if ( isset( $item['valueLogic'] ) && $item['valueLogic'] === 'any' ) {
			$this->value = null;
		}

		if ( $schema->get_join_column() ) {
			if ( $this->key || $this->key_logic === 'any' ) {
				$this->join_key = Sql_Join::create( $schema->get_column() . '_key', $schema->get_join_column() );
			}

			if ( $this->value || $this->value_logic === 'any' ) {
				$this->join_value = Sql_Join::create( $schema->get_column() . '_value', $schema->get_join_column() );
			}
		}

		$this->key_flags = new Search_Flags( isset( $item['keyFlags'] ) ? $item['keyFlags'] : [ 'case' ] );
		$this->value_flags = new Search_Flags( isset( $item['valueFlags'] ) ? $item['valueFlags'] : [ 'case' ] );
	}

	public function set_non_matching() {
		parent::set_non_matching();

		if ( $this->join_key ) {
			$this->join_key->set_non_matching();
		}

		if ( $this->join_value ) {
			$this->join_value->set_non_matching();
		}
	}

	public function to_json() {
		return [
			'column' => $this->schema->get_column(),
			'key' => $this->key,
			'value' => $this->value,
			'keyLogic' => $this->key_logic,
			'valueLogic' => $this->value_logic,
			'keyFlags' => $this->key_flags->to_json(),
			'valueFlags' => $this->value_flags->to_json(),
		];
	}

	public function is_valid() {
		return $this->key || $this->value;
	}

	public function get_values_for_row( $row ) {
		if ( isset( $row['meta_id'] ) ) {
			if ( $row['meta_id'] === '0' ) {
				return [ 'meta' => implode( ',', $this->join_key->get_all_values( intval( array_values( $row )[0], 10 ) ) ) ];
			}

			return [ 'meta' => $row['meta_id'] ];
		}

		return [];
	}

	public function get_query() {
		$query = new Sql_Query();

		if ( $this->join_key ) {
			$query->add_join( $this->join_key );
		}

		if ( $this->join_value ) {
			$query->add_join( $this->join_value );
		}

		if ( $this->is_valid() ) {
			$wheres = [];

			if ( $this->key ) {
				$wheres[] = new Sql_Where_String( new Sql_Select( Sql_Value::table( $this->join_key->get_table() ), Sql_Value::column( $this->schema->get_column() . '_key' ), Sql_Value::column( 'meta' ) ), $this->key_logic, $this->key, $this->key_flags );
			}

			if ( $this->value ) {
				// Sql_Value $table, Sql_Value $column, Sql_Value $alias = null, $prefix_required = false
				$wheres[] = new Sql_Where_String( new Sql_Select( Sql_Value::table( $this->join_value->get_table() ), Sql_Value::column( $this->schema->get_column() . '_value' ), Sql_Value::column( 'meta' ) ), $this->value_logic, $this->value, $this->value_flags );
			}

			$query->add_select( new Sql_Select_Column( $this->schema ) );
			$query->add_where( new Sql_Where_And( $wheres ) );
		}

		return $query;
	}

	public function get_column_data( $column, $value, Search_Source $source, Action $action ) {
		if ( ! $this->join_key || ! $this->join_value ) {
			return [];
		}

		$meta_contexts = [];
		$raw_values = array_values( array_filter( explode( ',', $value ) ) );
		if ( count( $raw_values ) === 0 ) {
			return [];
		}

		$raw_values = array_map( 'intval', $raw_values );
		$meta_values = $this->join_key ? $this->join_key->get_values( $raw_values ) : $this->join_value->get_values( $raw_values );

		foreach ( $raw_values as $pos => $value ) {
			$meta_contexts[ $value ] = [ 'key' => [], 'value' => [] ];

			// Get the key
			if ( $this->join_key ) {
				$context = $this->get_value_context( $source, $action, $value, $this->schema->get_column() . '_key', $meta_values[ $pos ]->meta_key );

				if ( count( $context ) > 0 ) {
					$meta_contexts[ $value ]['key'] = $context[0];
				}
			}

			// Get the value
			if ( $this->join_value ) {
				$context = $this->get_value_context( $source, $action, $value, $this->schema->get_column() . '_value', $meta_values[ $pos ]->meta_value );

				if ( count( $context ) > 0 ) {
					$meta_contexts[ $value ]['value'] = $context[0];
				}
			}
		}

		// Merge the contexts together
		$contexts = [];
		foreach ( $meta_contexts as $context ) {
			if ( $this->join_key && $this->join_value ) {
				$contexts[] = new Match_Context_Pair( $context['key'], $context['value'] );
			} elseif ( $this->join_key ) {
				$contexts[] = $context['key'];
			} elseif ( $this->join_value ) {
				$contexts[] = $context['value'];
			}
		}

		foreach ( $contexts as $pos => $context ) {
			$context->set_context_id( $pos );
		}

		return $contexts;
	}

	private function get_value_context( Search_Source $source, Action $action, $value, $column, $label ) {
		$value = intval( $value, 10 );

		// We can shortcut equals or notequals
		if ( $column === $this->schema->get_column() . '_key' ) {
			return $this->get_value( $source, $action, $this->key_logic, $this->key, $this->key_flags, $this->join_key, $value, $label );
		}

		return $this->get_value( $source, $action, $this->value_logic, $this->value, $this->value_flags, $this->join_value, $value, $label );
	}

	private function get_value( Search_Source $source, Action $action, $logic, $match_value, $flags, $join, $meta_id, $label ) {
		$matched = $label === $match_value;

		if ( ( $logic === 'equals' && $matched ) || ( $logic === 'notequals' && ! $matched ) ) {
			return $this->get_matched_context( $source, $label, $label );
		}

		if ( ( $logic === 'equals' && ! $matched ) || ( $logic === 'notequals' && $matched ) || $logic === 'any' ) {
			return $this->get_unmatched_context( $source, $label, $label );
		}

		$string = new Search_Filter_String( [], $this->schema );

		return $string->get_match( $source, $action, $logic, $match_value, $label, $flags );
	}
}
