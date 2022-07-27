<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Source;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Context;

/**
 * Filter a key/value column (meta data)
 */
class Filter_Keyvalue extends Filter_Type {
	/**
	 * Logic to use for key
	 *
	 * @readonly
	 * @var string
	 */
	private $key_logic = 'any';

	/**
	 * Logic to use for value
	 *
	 * @readonly
	 * @var string
	 */
	private $value_logic = 'any';

	/**
	 * Key value
	 *
	 * @readonly
	 * @var string|null
	 */
	private $key = null;

	/**
	 * Key value flags
	 *
	 * @readonly
	 * @var Search\Flags
	 */
	private $key_flags;

	/**
	 * Value
	 *
	 * @readonly
	 * @var string|null
	 */
	private $value = null;

	/**
	 * Value flags
	 *
	 * @readonly
	 * @var Search\Flags
	 */
	private $value_flags;

	/**
	 * Key join
	 *
	 * @readonly
	 * @var Sql\Join\Meta|null
	 */
	private $join_key = null;

	/**
	 * Value join
	 *
	 * @readonly
	 * @var Sql\Join\Meta|null
	 */
	private $join_value = null;

	public function __construct( array $item, Schema\Column $schema ) {
		parent::__construct( $item, $schema );

		if ( ! empty( $item['key'] ) ) {
			$this->key = $item['key'];
		}

		if ( ! empty( $item['value'] ) ) {
			$this->value = $item['value'];
		}

		if ( isset( $item['keyLogic'] ) && in_array( strtolower( $item['keyLogic'] ), Filter\Type\Filter_String::LOGIC, true ) ) {
			$this->key_logic = strtolower( $item['keyLogic'] );
		}

		if ( isset( $item['valueLogic'] ) && in_array( strtolower( $item['valueLogic'] ), Filter\Type\Filter_String::LOGIC, true ) ) {
			$this->value_logic = strtolower( $item['valueLogic'] );
		}

		if ( isset( $item['keyLogic'] ) && $item['keyLogic'] === 'any' ) {
			$this->key = null;
		}

		if ( isset( $item['valueLogic'] ) && $item['valueLogic'] === 'any' ) {
			$this->value = null;
		}

		$joiner = $schema->get_join_column();
		if ( $joiner !== null ) {
			if ( $this->key || $this->key_logic === 'any' ) {
				$join = Sql\Join\Join::create( $schema->get_column() . '_key', $joiner );

				if ( $join instanceof Sql\Join\Meta ) {
					$this->join_key = $join;
				}
			}

			if ( $this->value || $this->value_logic === 'any' ) {
				$join = Sql\Join\Join::create( $schema->get_column() . '_value', $joiner );

				if ( $join instanceof Sql\Join\Meta ) {
					$this->join_value = $join;
				}
			}
		}

		$this->key_flags = new Search\Flags( isset( $item['keyFlags'] ) ? $item['keyFlags'] : [ 'case' ] );
		$this->value_flags = new Search\Flags( isset( $item['valueFlags'] ) ? $item['valueFlags'] : [ 'case' ] );
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

	public function get_values_for_row( array $row ) {
		if ( isset( $row['meta_id'] ) && $this->join_key !== null ) {
			if ( $row['meta_id'] === '0' ) {
				return [ 'meta' => implode( ',', $this->join_key->get_all_values( intval( array_values( $row )[0], 10 ) ) ) ];
			}

			return [ 'meta' => $row['meta_id'] ];
		}

		return [];
	}

	public function get_query() {
		$query = new Sql\Query();

		if ( $this->join_key ) {
			$query->add_join( $this->join_key );
		}

		if ( $this->join_value ) {
			$query->add_join( $this->join_value );
		}

		if ( $this->is_valid() ) {
			$wheres = [];

			if ( $this->key && $this->join_key !== null ) {
				$wheres[] = new Sql\Where\Where_String( new Sql\Select\Select( Sql\Value::table( $this->join_key->get_table() ), Sql\Value::column( $this->schema->get_column() . '_key' ), Sql\Value::column( 'meta' ) ), $this->key_logic, $this->key, $this->key_flags );
			}

			if ( $this->value && $this->join_value !== null ) {
				$wheres[] = new Sql\Where\Where_String( new Sql\Select\Select( Sql\Value::table( $this->join_value->get_table() ), Sql\Value::column( $this->schema->get_column() . '_value' ), Sql\Value::column( 'meta' ) ), $this->value_logic, $this->value, $this->value_flags );
			}

			$query->add_select( new Sql\Select\Select_Column( $this->schema ) );
			$query->add_where( new Sql\Where\Where_And( $wheres ) );
		}

		return $query;
	}

	public function get_column_data( $column, $value, Source\Source $source, Action\Action $action ) {
		$meta_contexts = [];
		$raw_values = array_values( array_filter( explode( ',', $value ) ) );
		if ( count( $raw_values ) === 0 ) {
			return [];
		}

		$raw_values = array_map( 'intval', $raw_values );
		$meta_values = [];
		if ( $this->join_key ) {
			$meta_values = $this->join_key->get_values( $raw_values );
		} elseif ( $this->join_value ) {
			$meta_values = $this->join_value->get_values( $raw_values );
		}

		foreach ( $raw_values as $pos => $value ) {
			$meta_contexts[ $value ] = [
				'key' => [],
				'value' => [],
			];

			// Get the key
			if ( $this->join_key ) {
				$context = $this->get_value_context( $source, $action, $this->schema->get_column() . '_key', $meta_values[ $pos ]->meta_key );

				if ( count( $context ) > 0 ) {
					$meta_contexts[ $value ]['key'] = $context[0];
				}
			}

			// Get the value
			if ( $this->join_value ) {
				$context = $this->get_value_context( $source, $action, $this->schema->get_column() . '_value', $meta_values[ $pos ]->meta_value );

				if ( count( $context ) > 0 ) {
					$meta_contexts[ $value ]['value'] = $context[0];
				}
			}
		}

		// Merge the contexts together
		$contexts = [];
		foreach ( $meta_contexts as $context ) {
			if ( $this->join_key && $this->join_value && $context['key'] instanceof Context\Type\Value && $context['value'] instanceof Context\Type\Value ) {
				$contexts[] = new Context\Type\Pair( $context['key'], $context['value'] );
			} elseif ( $this->join_key ) {
				$contexts[] = $context['key'];
			} elseif ( $this->join_value ) {
				$contexts[] = $context['value'];
			}
		}

		$contexts = array_filter( $contexts );

		foreach ( $contexts as $pos => $context ) {
			$context->set_context_id( $pos );
		}

		return $contexts;
	}

	/**
	 * Get contexts for a value
	 *
	 * @param Source\Source $source Source.
	 * @param Action\Action $action Action.
	 * @param string        $column Column.
	 * @param string        $label Label.
	 * @return array
	 */
	private function get_value_context( Source\Source $source, Action\Action $action, $column, $label ) {
		// We can shortcut equals or notequals
		if ( $column === $this->schema->get_column() . '_key' && $this->key !== null ) {
			return $this->get_value( $source, $action, $this->key_logic, $this->key, $this->key_flags, $label );
		}

		if ( $this->value !== null ) {
			return $this->get_value( $source, $action, $this->value_logic, $this->value, $this->value_flags, $label );
		}

		return [];
	}

	/**
	 * Get context for a keyvalue match
	 *
	 * @param Source\Source $source Source.
	 * @param Action\Action $action Action.
	 * @param string        $logic Logic.
	 * @param string        $match_value Value.
	 * @param Search\Flags  $flags Flags.
	 * @param string        $label Label.
	 * @return Context\Context[]
	 */
	private function get_value( Source\Source $source, Action\Action $action, $logic, $match_value, $flags, $label ) {
		$matched = $label === $match_value;
		$equal_match = $logic === 'equals' && $matched;
		$notequal_unmatched = $logic === 'notequals' && ! $matched;

		if ( $equal_match || $notequal_unmatched ) {
			return $this->get_matched_context( $source, $label, $label );
		}

		$equal_unmatch = $logic === 'equals' && ! $matched;
		$notequal_matched = $logic === 'notequals' && $matched;
		if ( $equal_unmatch || $notequal_matched || $logic === 'any' ) {
			return $this->get_unmatched_context( $source, $label, $label );
		}

		$string = new Filter\Type\Filter_String( [], $this->schema );

		return $string->get_match( $source, $action, $logic, $match_value, $label, $flags );
	}
}
