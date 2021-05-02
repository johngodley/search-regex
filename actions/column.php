<?php

namespace SearchRegex;

abstract class Action_Modify_Column {
	protected $operation = null;
	protected $schema = null;

	public function __construct( $option, Schema_Column $schema ) {
		$this->schema = $schema;
	}

	public function get_source_name() {
		return $this->schema->get_source();
	}

	public function get_column_name() {
		return $this->schema->get_column();
	}

	public function get_schema() {
		return $this->schema;
	}

	public function is_for_column( $column ) {
		return $this->get_column_name() === $column;
	}

	public function get_row_data( array $row ) {
		if ( isset( $row[ $this->get_column_name() ] ) ) {
			return $row[ $this->get_column_name() ];
		}

		return false;
	}

	public static function create( $option, Schema_Source $schema ) {
		$column = $schema->get_column( isset( $option['column'] ) ? $option['column'] : null );
		if ( ! $column ) {
			return null;
		}

		if ( $column->get_type() === 'integer' ) {
			return new Action_Modify_Integer( $option, $column );
		}

		if ( $column->get_type() === 'string' ) {
			return new Action_Modify_String( $option, $column );
		}

		if ( $column->get_type() === 'date' ) {
			return new Action_Modify_Date( $option, $column );
		}

		if ( $column->get_type() === 'member' ) {
			return new Action_Modify_Member( $option, $column );
		}

		if ( $column->get_type() === 'keyvalue' ) {
			return new Action_Modify_Keyvalue( $option, $column );
		}

		return null;
	}

	public function is_valid() {
		return $this->operation !== null && $this->schema !== null;
	}

	public function get_change( $value ) {
		return null;
	}

	public function to_json() {
		return [
			'column' => $this->get_column_name(),
			'source' => $this->get_source_name(),
		];
	}

	abstract public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw );
}

require_once __DIR__ . '/column/string.php';
require_once __DIR__ . '/column/member.php';
require_once __DIR__ . '/column/integer.php';
require_once __DIR__ . '/column/date.php';
require_once __DIR__ . '/column/keyvalue.php';
