<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Modifier;
use SearchRegex\Source;
use SearchRegex\Context;

class Key_Value extends Modifier\Modifier {
	/**
	 * Array of key/value pairs
	 *
	 * @var array
	 */
	private $items = [];

	public function __construct( array $option, Schema\Column $schema ) {
		parent::__construct( $option, $schema );

		$this->operation = 'add';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'add', 'remove' ], true ) ) {
			$this->operation = $option['operation'];
		}

		if ( isset( $option['items'] ) ) {
			$this->items = array_values( array_filter( array_map( [ $this, 'add_item' ], $option['items'] ) ) );
		}

		if ( isset( $option['key'] ) ) {
			$this->items = [ $this->add_item( $option ) ];
		}
	}

	public function to_json() {
		return array_merge(
			parent::to_json(),
			[
				'operation' => $this->operation,
				'items' => $this->items,
			]
		);
	}

	public function get_row_data( array $row ) {
		if ( isset( $row['meta_id'] ) ) {
			return $row['meta_id'];
		}

		return false;
	}

	/**
	 * Add an item to the list of items if it is valid, otherwise return null
	 *
	 * @param array|string $item Item.
	 * @return array|null
	 */
	public function add_item( $item ) {
		if ( ! is_array( $item ) ) {
			return null;
		}

		$new_item = [];
		if ( isset( $item['key'] ) && isset( $item['value'] ) ) {
			$new_item['key'] = $item['key'];
			$new_item['value'] = $item['value'];

			if ( isset( $item['type'] ) ) {
				$new_item['type'] = $item['type'];
			}

			$new_item['value_type'] = 'value';
			if ( isset( $item['value_type'] ) ) {
				$new_item['value_type'] = $item['value_type'];
			}

			return $new_item;
		}

		return null;
	}

	public function is_for_column( $column ) {
		return $column === $this->schema->get_column() . '_key' || $column === $this->schema->get_column() . '_value' || $column === $this->schema->get_column();
	}

	public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode ) {
		$replace = [];

		// Go through contexts and find the matching action that modifies it
		foreach ( $column->get_contexts() as $pos => $context ) {
			$match = $this->items[ $pos ];

			if ( $match['type'] === 'delete' ) {
				$replace[] = new Context\Type\Pair( new Context\Type\Delete( $match['key'] ), new Context\Type\Delete( $match['value'] ) );
			} elseif ( $context instanceof Context\Type\Pair && ( $match['type'] === 'replace' || $match['value_type'] === 'replace' ) ) {
				$key = $context->get_key();

				if ( $match['type'] === 'replace' && $key instanceof Context\Type\Value ) {
					$key = new Context\Type\Replace( $key->get_value() );
					$key->set_replacement( $match['key'] );
				}

				$value = $context->get_value();
				if ( $match['value_type'] === 'replace' && $value instanceof Context\Type\Value ) {
					$value = new Context\Type\Replace( $value->get_value() );
					$value->set_replacement( $match['value'] );
				}

				$replace[] = new Context\Type\Pair( $key, $value );
			} else {
				$replace[] = $context;
			}
		}

		// Go through items and add any new ones
		foreach ( array_slice( $this->items, count( $column->get_contexts() ) ) as $item ) {
			$replace[] = new Context\Type\Pair( new Context\Type\Add( $item['key'] ), new Context\Type\Add( $item['value'] ) );
		}

		$column->set_contexts( $replace );

		return $column;
	}
}
