<?php

namespace SearchRegex\Sql;

class Sql_Where {
	protected $column = null;
	protected $logic = '=';
	protected $value = null;

	protected function __construct( Sql_Select $column, $logic, $value ) {
		$this->column = $column;
		$this->value = $value;  // Sanitized in get_value
		$this->logic = $logic;
	}

	public function get_as_sql() {
		return $this->column->get_column_or_alias() . ' ' . $this->logic . ' ' . $this->get_value();
	}

	public function update_column( $column, $updated_column ) {
		$this->column->update_column( $column, $updated_column );
	}

	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%s', $this->value );
	}
}

require_once __DIR__ . '/or.php';
require_once __DIR__ . '/and.php';
require_once __DIR__ . '/integer.php';
require_once __DIR__ . '/string.php';
require_once __DIR__ . '/date.php';
require_once __DIR__ . '/in.php';
require_once __DIR__ . '/null.php';
