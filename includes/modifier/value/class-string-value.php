<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Modifier;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Context;
use SearchRegex\Filter;
use SearchRegex\Action;
use WP_Error;

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

		[ $before, $after ] = $this->get_markers();

		// Global replace
		$result = $this->replace_all( $this->search_value, $this->replace_value, $value, $before, $after );
		if ( $result === null ) {
			return [];
		}

		// Split into array
		if ( \preg_match_all( '@' . $before . '(.*?)' . $after . '@s', $result, $searches ) > 0 ) {
			return $searches[1];
		}

		return [];
	}

	/**
	 * Get the text used to mark the start and end of each replacement. This is random so that it cannot already be in the content.
	 *
	 * @return array{string, string}
	 */
	private function get_markers() {
		$marker = bin2hex( random_bytes( 8 ) );

		return [ '<SEARCHREGEX-' . $marker . '>', '</SEARCHREGEX-' . $marker . '>' ];
	}

	/**
	 * Perform a global replacement
	 *
	 * @internal
	 * @param string $search Search string.
	 * @param string $replace Replacement value.
	 * @param string $value Content to replace.
	 * @param string $before Text to insert before each replacement.
	 * @param string $after Text to insert after each replacement.
	 * @return string|null The replaced content, or null if the replacement failed
	 */
	private function replace_all( $search, $replace, $value, $before = '', $after = '' ) {
		$pattern = Search\Text::get_pattern( $search, $this->search_flags );

		if ( ! $this->search_flags->is_regex() ) {
			// A plain text replacement is literal, so stop $ and \ being treated as a backreference
			$replace = addcslashes( $replace, '\\$' );
		}

		// Global replace. This returns null on failure, such as invalid UTF-8 in the content or hitting a PCRE limit
		return preg_replace( $pattern, $before . $replace . $after, $value );
	}

	/**
	 * Perform a global replacement, with any shortcodes in the replacement processed for each match.
	 *
	 * Shortcodes are only processed in the replacement, and not in the rest of the content.
	 *
	 * @internal
	 * @param string $search Search string.
	 * @param string $replace Replacement value.
	 * @param int $row_id Row ID.
	 * @param string $row_value Content to replace.
	 * @param array<string, mixed> $raw Raw database data.
	 * @param Source\Source $source Source.
	 * @return string|null The replaced content, or null if the replacement failed
	 */
	private function replace_all_dynamic( $search, $replace, $row_id, $row_value, array $raw, Source\Source $source ) {
		[ $before, $after ] = $this->get_markers();

		$replaced = $this->replace_all( $search, $replace, $row_value, $before, $after );
		if ( $replaced === null ) {
			return null;
		}

		return preg_replace_callback(
			'@' . $before . '(.*?)' . $after . '@s',
			fn( $matches ) => apply_filters( 'searchregex_text', $matches[1], $row_id, $row_value, $raw, $source->get_schema_item() ),
			$replaced
		);
	}

	/**
	 * Determine if a search can be used as a pattern. A plain text search is always valid.
	 *
	 * @param string $search Search string.
	 * @return bool
	 */
	private function is_valid_pattern( $search ) {
		if ( ! $this->search_flags->is_regex() ) {
			return true;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- an invalid pattern raises a PHP warning
		return @preg_match( Search\Text::get_pattern( $search, $this->search_flags ), '' ) !== false;
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

		// An invalid pattern will fail for every row, so report it rather than silently change nothing
		if ( ! $this->is_valid_pattern( $this->search_value ) ) {
			return new WP_Error( 'rest_invalid_param', 'Invalid regular expression: ' . $this->search_value, [ 'status' => 400 ] );
		}

		// Leave serialized data untouched
		if ( is_serialized( $row_value ) ) {
			return $column;
		}

		if ( $this->pos_id === null ) {
			if ( $this->replace_value === null ) {
				return $column;
			}

			// When not saving we need to return the individual replacements. If saving then we want to return the whole text
			if ( $save_mode ) {
				$value = $this->replace_all_dynamic( $this->search_value, $this->replace_value, $row_id, $row_value, $raw, $source );
				if ( $value === null ) {
					// The replacement failed, so leave the row untouched rather than save an empty value
					return $column;
				}

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
				$replacement = $match->get_replacement();
				if ( $replacement !== null ) {
					// Only the replacement is dynamic. Shortcodes in the rest of the content are left alone
					$match->set_replacement( apply_filters( 'searchregex_text', $replacement, $row_id, $row_value, $raw, $source->get_schema_item() ) );
				}

				$value = $match->replace_at_position( $row_value );

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
