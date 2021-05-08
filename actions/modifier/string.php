<?php

namespace SearchRegex;

/**
 * Modify a string
 */
class Modify_String extends Modifier {
	const BEFORE = '<SEARCHREGEX>';
	const AFTER = '</SEARCHREGEX>';

	/**
	 * Value to search for. Only used in a search/replace
	 *
	 * @var string|null
	 */
	private $search_value = null;

	/**
	 * Value to replace, or the column value in a 'set'
	 *
	 * @var string|null
	 */
	private $replace_value = null;

	/**
	 * Search flags
	 *
	 * @var Search_Flags
	 */
	private $search_flags;

	/**
	 * Position within the column to replace
	 *
	 * @var integer|null
	 */
	private $pos_id = null;

	public function __construct( array $option, Schema_Column $schema ) {
		parent::__construct( $option, $schema );

		if ( isset( $option['searchValue'] ) ) {
			$this->search_value = $option['searchValue'];
		}

		if ( isset( $option['replaceValue'] ) ) {
			$this->replace_value = $option['replaceValue'];
		}

		$this->operation = 'set';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'set', 'replace' ], true ) ) {
			$this->operation = $option['operation'];
		}

		if ( isset( $option['posId'] ) ) {
			$this->pos_id = intval( $option['posId'], 10 );
		}

		$flags = isset( $option['searchFlags'] ) ? $option['searchFlags'] : [ 'case' ];
		if ( ! is_array( $flags ) ) {
			$flags = [ $flags ];
		}

		$this->search_flags = new Search_Flags( $flags );
	}

	public function is_valid() {
		if ( $this->operation === 'replace' && $this->search_value && strlen( $this->search_value ) === 0 ) {
			return false;
		}

		return parent::is_valid();
	}

	public function to_json() {
		return array_merge(
			parent::to_json(),
			[
				'operation' => $this->operation,
				'searchValue' => $this->search_value,
				'replaceValue' => $this->replace_value,
				'searchFlags' => $this->search_flags->to_json(),
			]
		);
	}

	/**
	 * Return all the replace positions - the positions within the content where the search is matched.
	 *
	 * @param string $value Value to search and replace within.
	 * @return Array Array of match positions
	 */
	public function get_replace_positions( $value ) {
		if ( ! $this->search_value || ! $this->replace_value ) {
			return [];
		}

		// Global replace
		$result = $this->replace_all( $this->search_value, self::BEFORE . $this->replace_value . self::AFTER, $value );

		// Split into array
		$pattern = '@' . self::BEFORE . '(.*?)' . self::AFTER . '@';
		if ( $this->search_flags->is_case_insensitive() ) {
			$pattern .= 'i';
		}

		if ( \preg_match_all( $pattern, $result, $searches ) > 0 ) {
			return $searches[1];
		}

		return [];
	}

	/**
	 * Perform a global replacement
	 *
	 * @internal
	 * @param String $search Search string.
	 * @param String $replace Replacement value.
	 * @param String $value Content to replace.
	 * @return String
	 */
	private function replace_all( $search, $replace, $value ) {
		$pattern = Matched_Item::get_pattern( $search, $this->search_flags );

		if ( ! $this->search_flags->is_regex() && is_serialized( $value ) ) {
			$serial = '/s:(\d*):"(.*?)";/s';

			return preg_replace_callback( $serial, function( $matches ) use ( $search, $replace ) {
				if ( strpos( $matches[2], $search ) !== false ) {
					$replaced = str_replace( $search, $replace, $matches[2] );

					return 's:' . (string) strlen( $replaced ) . ':"' . $replaced . '";';
				}

				return $matches[0];
			}, $value );
		}

		// Global replace
		return preg_replace( $pattern, $replace, $value );
	}

	public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw ) {
		if ( $this->operation === 'set' ) {
			/**
			 * @psalm-suppress TooManyArguments
			 */
			$value = apply_filters( 'searchregex_text', $this->replace_value, $row_id, $row_value, $raw, $source->get_schema_item() );

			$replacement = new Match_Context_Replace( $row_value );
			$replacement->set_replacement( $value );
			$column->set_contexts( [ $replacement ] );

			return $column;
		}

		if ( ! $this->search_value ) {
			return $column;
		}

		if ( $this->pos_id === null ) {
			if ( ! $this->replace_value ) {
				return $column;
			}

			$global_replace = $this->replace_all( $this->search_value, $this->replace_value, $row_value );

			/**
			 * @psalm-suppress TooManyArguments
			 */
			$value = apply_filters( 'searchregex_text', $global_replace, $row_id, $row_value, $raw, $source->get_schema_item() );

			// Global replace
			$replacement = new Match_Context_Replace( $row_value );
			$replacement->set_replacement( $value );
			$column->set_contexts( [ $replacement ] );

			return $column;
		}

		// Replace a specific position
		$replacements = $this->get_replace_positions( $this->search_value );
		$contexts = Matched_Item::get_all( $this->search_value, $this->search_flags, $replacements, $row_value );

		foreach ( $contexts as $context ) {
			$match = $context->get_match_at_position( $this->pos_id );

			if ( is_object( $match ) ) {
				/**
				 * @psalm-suppress TooManyArguments
				 */
				$value = apply_filters( 'searchregex_text', $match->replace_at_position( $row_value ), $row_id, $row_value, $raw, $source->get_schema_item() );

				// Need to replace the match with the result in the raw data
				$context = new Match_Context_Replace( $row_value );
				$context->set_replacement( $value );
				$column->set_contexts( [ $context ] );

				return $column;
			}
		}

		return $column;
	}

	/**
	 * Get replacement value
	 *
	 * @return string|null
	 */
	public function get_replace_value() {
		return $this->replace_value;
	}
}
