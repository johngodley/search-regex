<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Source;
use SearchRegex\Modifier;
use SearchRegex\Context;

/**
 * @phpstan-type IntegerModifierOption array{
 *   column?: string,
 *   operation?: 'set'|'increment'|'decrement',
 *   value?: mixed
 * }
 */
class Integer_Value extends Modifier\Modifier {
	/**
	 * Modification Value
	 */
	private ?int $value = null;

	/**
	 * Constructor
	 *
	 * @param IntegerModifierOption $option Integer modification options.
	 * @param Schema\Column $schema Schema.
	 */
	public function __construct( $option, Schema\Column $schema ) {
		parent::__construct( $option, $schema );

		if ( isset( $option['value'] ) ) {
			$this->value = intval( $option['value'], 10 );
		}

		$this->operation = 'set';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'set', 'increment', 'decrement' ], true ) ) {
			$this->operation = $option['operation'];
		}
	}

	public function to_json() {
		$parent_json = parent::to_json();

		return [
			'column' => $parent_json['column'],
			'source' => $parent_json['source'],
			'operation' => $this->operation,
			'value' => $this->value,
		];
	}

	public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode ) {
		// Go through contexts and find the matching action that modifies it
		if ( count( $column->get_contexts() ) === 1 && $this->value !== null ) {
			$context = $column->get_contexts()[0];
			if ( ! $context instanceof Context\Type\Value ) {
				return $column;
			}

			$value = $context->get_value();

			if ( $this->operation === 'increment' ) {
				$replacement = intval( $value, 10 ) + $this->value;
				$context = new Context\Type\Replace( $value );
				$context->set_replacement( "{$replacement}", $source->convert_result_value( $this->schema, (string) $replacement ) );
			} elseif ( $this->operation === 'decrement' ) {
				$replacement = max( 0, intval( $value, 10 ) - $this->value );
				$context = new Context\Type\Replace( $value );
				$context->set_replacement( "{$replacement}", $source->convert_result_value( $this->schema, (string) $replacement ) );
			} elseif ( intval( $this->value, 10 ) !== intval( $value, 10 ) ) {
				$context = new Context\Type\Replace( $value );
				$context->set_replacement( "{$this->value}", $source->convert_result_value( $this->schema, (string) $this->value ) );
			}

			$column->set_contexts( [ $context ] );
		}

		return $column;
	}
}
