<?php

namespace SearchRegex\Sql;

class Sql_Value {
	public function __construct( $value ) {
		$this->value = $value;
	}

	public function get_value() {
		return $this->value;
	}

	public static function raw( $value ) {
		return new Sql_Value( $value );
	}

	public static function column( $value ) {
		$value = preg_replace( '/[^ A-Za-z0-9_\-\.]/', '', $value );

		return new Sql_Value( $value );
	}

	public static function table( $value ) {
		$value = preg_replace( '/[^A-Za-z0-9_\-]/', '', $value );

		return new Sql_Value( $value );
	}
}
