<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Modifier;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Context;
use SearchRegex\Filter;
use SearchRegex\Action;

/**
 * Modify a string
 *
 * @phpstan-type StringModifierOption array{
 *   column?: string,
 *   operation?: 'set'|'replace',
 *   searchValue?: string,
 *   replaceValue?: string,
 *   posId?: int|string,
 *   searchFlags?: array<'case'|'regex'>|'case'|'regex'
 * }
 */
class String_Value extends Modifier\Modifier {
	const BEFORE = '<SEARCHREGEX>';
	const AFTER = '</SEARCHREGEX>';

	/**
	 * Value to search for. Only used in a search/replace
	 */
	private ?string $search_value = null;

	/**
	 * Value to replace, or the column value in a 'set'
	 */
	private ?string $replace_value = null;

	/**
	 * Search flags
	 */
	private Search\Flags $search_flags;

	/**
	 * Position within the column to replace
	 */
	private ?int $pos_id = null;

	/**
	 * Constructor
	 *
	 * @param StringModifierOption $option String modification options.
	 * @param Schema\Column $schema Schema.
	 */
	public function __construct( $option, Schema\Column $schema ) {
		parent::__construct( $option, $schema );

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $option['searchValue'] ) && is_string( $option['searchValue'] ) ) {
			$this->search_value = $option['searchValue'];
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $option['replaceValue'] ) && is_string( $option['replaceValue'] ) ) {
			$this->replace_value = $option['replaceValue'];
		}

		$this->operation = 'set';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'set', 'replace' ], true ) ) {
			$this->operation = $option['operation'];
		}

		if ( isset( $option['posId'] ) ) {
			$this->pos_id = intval( $option['posId'], 10 );
		}

		$flags = $option['searchFlags'] ?? [ 'case' ];
		if ( ! is_array( $flags ) ) {
			$flags = [ $flags ];
		}

		$this->search_flags = new Search\Flags( $flags );
	}

	public function is_valid() {
		if ( $this->operation === 'replace' && $this->search_value === '' ) {
			return false;
		}

		return parent::is_valid();
	}

	public function to_json() {
		$parent_json = parent::to_json();

		return [
			'column' => $parent_json['column'],
			'source' => $parent_json['source'],
			'operation' => $this->operation,
			'searchValue' => $this->search_value,
			'replaceValue' => $this->replace_value,
			'searchFlags' => $this->search_flags->to_json(),
		];
	}

	/**
	 * Return all the replace positions - the positions within the content where the search is matched.
	 *
	 * @param string $value Value to search and replace within.
	 * @return array<int, string> Array of match positions
	 */
	public function get_replace_positions( $value ) {
		if ( ! $this->search_value || $this->replace_value === null ) {
			return [];
		}

		$replace_value = $this->replace_value;
		if ( ! $this->search_flags->is_regex() ) {
			// Escape the replace value, in case it has a $ in it
			$replace_value = \preg_replace( '/(?<!\\\)\$/', '\\$', $this->replace_value );
		}

		// Global replace
		$result = $this->replace_all( $this->search_value, self::BEFORE . $replace_value . self::AFTER, $value );

		// Split into array
		$pattern = '@' . self::BEFORE . '(.*?)' . self::AFTER . '@s';
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
	 * @param string $search Search string.
	 * @param string $replace Replacement value.
	 * @param string $value Content to replace.
	 * @return string
	 */
	private function replace_all( $search, $replace, $value ) {
		$pattern = Search\Text::get_pattern( $search, $this->search_flags );

		if ( ! $this->search_flags->is_regex() && is_serialized( $value ) ) {
			$serial = '/s:(\d*):"(.*?)";/s';

			return (string) preg_replace_callback(
				$serial, function ( $matches ) use ( $search, $replace ) {
					if ( strpos( $matches[2], $search ) !== false ) {
						$replaced = str_replace( $search, $replace, $matches[2] );

						return 's:' . (string) strlen( $replaced ) . ':"' . $replaced . '";';
					}

					return $matches[0];
				}, $value
			);
		}

		// Global replace
		return (string) preg_replace( $pattern, $replace, $value );
	}

	public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode ) {
		if ( $this->operation === 'set' ) {
			// Identical - just return value
			if ( $this->replace_value === $row_value ) {
				return $column;
			}

			$value = apply_filters( 'searchregex_text', $this->replace_value, $row_id, $row_value, $raw, $source->get_schema_item() );

			if ( $value !== $row_value ) {
				$replacement = new Context\Type\Replace( $row_value );
				$replacement->set_replacement( $value );
				$column->set_contexts( [ $replacement ] );
			}

			return $column;
		}

		if ( ! $this->search_value ) {
			return $column;
		}

		if ( $this->pos_id === null ) {
			if ( $this->replace_value === null ) {
				return $column;
			}

			// When not saving we need to return the individual replacements. If saving then we want to return the whole text
			if ( $save_mode ) {
				$global_replace = $this->replace_all( $this->search_value, $this->replace_value, $row_value );

				$value = apply_filters( 'searchregex_text', $global_replace, $row_id, $row_value, $raw, $source->get_schema_item() );

				// Global replace
				if ( $row_value !== $value ) {
					$replacement = new Context\Type\Replace( $row_value );
					$replacement->set_replacement( $value );
					$column->set_contexts( [ $replacement ] );
				}

				return $column;
			}

			$replacements = $this->get_replace_positions( $row_value );

			$filter = new Filter\Type\Filter_String(
				[
					'value' => $this->search_value,
					'logic' => 'contains',
					'flags' => $this->search_flags->to_json(),
				], $this->schema
			);
			$matches = $filter->get_match( $source, new Action\Type\Nothing(), 'contains', $this->search_value, $row_value, $this->search_flags, $replacements );

			// If we replaced anything then update the context with our new matches, otherwise just return whatever we have
			if ( ( count( $matches ) === 1 && ! $matches[0] instanceof Context\Type\Value ) || count( $matches ) > 1 ) {
				$column->set_contexts( $matches );
			}

			return $column;
		}

		// Replace a specific position
		$replacements = $this->get_replace_positions( $row_value );
		$contexts = Search\Text::get_all( $this->search_value, $this->search_flags, $replacements, $row_value );

		foreach ( $contexts as $context ) {
			$match = $context->get_match_at_position( $this->pos_id );

			if ( is_object( $match ) ) {
				$value = apply_filters( 'searchregex_text', $match->replace_at_position( $row_value ), $row_id, $row_value, $raw, $source->get_schema_item() );

				// Need to replace the match with the result in the raw data
				if ( $row_value !== $value ) {
					$context = new Context\Type\Replace( $row_value );
					$context->set_replacement( $value );
					$column->set_contexts( [ $context ] );
				}

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
