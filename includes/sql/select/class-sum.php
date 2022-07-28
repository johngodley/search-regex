<?php

namespace SearchRegex\Sql\Select;

use SearchRegex\Schema;
use SearchRegex\Sql;

/**
 * @psalm-suppress all
 */
class Select_Phrases extends Select_Column {
	/**
	 * Values
	 *
	 * @var array
	 */
	private $values = [];

	/**
	 * Constructor
	 *
	 * @param Schema\Column  $column Column.
	 * @param Sql\Value|null $alias Alias.
	 */
	public function __construct( Schema\Column $column, Sql\Value $alias = null ) {
		parent::__construct( $column, Sql\Value::column( 'match_total' ) );

		if ( $alias !== null ) {
			// Not sanitized until later
			$this->values = [
				[
					'column' => $column->get_column(),
					'value' => $alias->get_value(),
				],
			];
		}
	}

	public function get_as_sql() {
		return '';
	}

	/**
	 * Add
	 *
	 * @param Select_Phrases $phrase Phrase.
	 * @return void
	 */
	public function add_sum( Select_Phrases $phrase ) {
		$this->values = array_merge( $this->values, $phrase->values );
	}

	/**
	 * Get SELECT
	 *
	 * @return string
	 */
	public function get_select_sql() {
		global $wpdb;

		$sum = [];

		foreach ( $this->values as $item ) {
			$column = $item['column'];
			$value = $item['value'];
			$cropped = mb_substr( $value, 0, mb_strlen( $value, 'UTF-8' ) - 1, 'UTF-8' );

			// phpcs:ignore
			$sum[] = $wpdb->prepare( "SUM(CHAR_LENGTH($column) - CHAR_LENGTH(REPLACE(UPPER($column), UPPER(%s), UPPER(%s))))", $value, $cropped );
		}

		return implode( ' + ', $sum ) . ( $this->alias ? ' as ' . $this->alias : '' );
	}
}
