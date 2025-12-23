<?php

namespace SearchRegex\Search;

/**
 * Represents flags for a particular search
 *
 * @phpstan-type SearchFlag 'regex'|'case'
 */
class Flags {
	/**
	 * @var SearchFlag[]
	 */
	private array $flags = [];

	/**
	 * Create a Flags object with an array of flag strings
	 *
	 * @param SearchFlag[] $flags Array of flag values.
	 */
	public function __construct( array $flags = [] ) {
		$allowed = [
			'regex',
			'case',
		];

		$this->flags = array_filter(
			$flags, fn( $flag ) => array_search( $flag, $allowed, true ) !== false
		);
	}

	/**
	 * Duplicate a search flag object
	 *
	 * @param Flags $flags Flags.
	 * @return Flags
	 */
	public static function copy( Flags $flags ) {
		return new Flags( $flags->flags );
	}

	/**
	 * Is the flag set?
	 *
	 * @param string $flag Flag to check.
	 * @return boolean true if set, false otherwise
	 */
	public function has_flag( $flag ) {
		return in_array( $flag, $this->flags, true );
	}

	/**
	 * Is the a regular expression search?
	 *
	 * @return boolean true if yes, false otherwise
	 */
	public function is_regex() {
		return $this->has_flag( 'regex' );
	}

	/**
	 * Is the a case insensitive search?
	 *
	 * @return boolean true if yes, false otherwise
	 */
	public function is_case_insensitive() {
		return $this->has_flag( 'case' );
	}

	/**
	 * Set the regex flag
	 *
	 * @return void
	 */
	public function set_regex() {
		if ( ! $this->is_regex() ) {
			$this->flags[] = 'regex';
		}
	}

	/**
	 * Get all the flags
	 *
	 * @return SearchFlag[] Array of flags
	 */
	public function get_flags() {
		return $this->flags;
	}

	/**
	 * Convert the flags to JSON
	 *
	 * @return SearchFlag[]
	 */
	public function to_json() {
		return $this->flags;
	}
}
