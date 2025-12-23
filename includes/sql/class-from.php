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
	 */
	private string $table;

	/**
	 * Table alias
	 */
	private ?string $alias = null;

	/**
	 * Constructor
	 *
	 * @param Value $table Table name.
	 * @param Value|null $alias Table alias.
	 */
	public function __construct( Value $table, ?Value $alias = null ) {
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
