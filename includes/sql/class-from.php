<?php

namespace SearchRegex\Sql;

/**
 * Sql FROM
 */
class From {
	/**
	 * Table name
	 *
	 * @readonly
	 * @var string
	 */
	private $table;

	/**
	 * Table alias
	 *
	 * @var string|null
	 */
	private $alias = null;

	public function __construct( Value $table, Value $alias = null ) {
		$this->table = $table->get_value();
		$this->alias = $alias ? $alias->get_value() : null;
	}

	/**
	 * Get the FROM as SQL
	 *
	 * @return string
	 */
	public function get_as_sql() {
		if ( $this->alias && $this->alias !== $this->table ) {
			return $this->table . ' AS ' . $this->alias;
		}

		return $this->table;
	}
}
