<?php

namespace SearchRegex;

/**
 * Represents flags for a particular source
 */
class Source_Flags {
	/** @var Array */
	private $flags = [];

	/**
	 * Create a Source_Flags object with an array of flag strings
	 *
	 * @param Array $flags Array of flag values.
	 */
	public function __construct( array $flags = [] ) {
		$this->flags = $flags;
	}

	/**
	 * Set which flags are allowed, and remove any existing flags that don't match
	 *
	 * @param Array $allowed Array of allowed flags.
	 * @return void
	 */
	public function set_allowed_flags( array $allowed ) {
		$this->flags = array_filter( $this->flags, function( $flag ) use ( $allowed ) {
			return array_search( $flag, $allowed, true ) !== false;
		} );
	}

	/**
	 * Is the flag set?
	 *
	 * @param String $flag Flag to check.
	 * @return boolean true if set, false otherwise
	 */
	public function has_flag( $flag ) {
		return in_array( $flag, $this->flags, true );
	}

	/**
	 * Get all the flags
	 *
	 * @return Array Array of flags
	 */
	public function get_flags() {
		return $this->flags;
	}

	public function to_json() {
		return $this->flags;
	}
}
