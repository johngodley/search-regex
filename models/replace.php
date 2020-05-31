<?php

namespace SearchRegex;

use SearchRegex\Result_Collection;
use SearchRegex\Match;

/**
 * Performs plain and regular expressions of single and global replacements
 */
class Replace {
	/**
	 * The replacement phrase
	 *
	 * @var String
	 **/
	private $replace;

	/**
	 * Our search sources
	 *
	 * @var Search_Source[]
	 **/
	private $sources = [];

	/**
	 * Our search flags
	 *
	 * @var Search_Flags
	 **/
	private $flags;

	const BEFORE = '<SEARCHREGEX>';
	const AFTER = '</SEARCHREGEX>';

	/**
	 * Create a Replace object given a replacement value, the Search_Sources, and Search_Flags
	 *
	 * @param String       $replace_value Value to replace with.
	 * @param Array        $sources Array of Search_Sources. Only one is currently supported.
	 * @param Search_Flags $flags The Search_Flags.
	 */
	public function __construct( $replace_value, array $sources, Search_Flags $flags ) {
		$this->replace = $replace_value;
		$this->flags = $flags;
		$this->sources = $sources;
	}

	/**
	 * Return all the replace positions - the positions within the content where the search is matched.
	 *
	 * @param String $search Search value.
	 * @param String $value Content to search.
	 * @return Array Array of match positions
	 */
	public function get_replace_positions( $search, $value ) {
		// Global replace
		$result = $this->replace_all( $search, self::BEFORE . $this->replace . self::AFTER, $value );

		// Split into array
		$pattern = '@' . self::BEFORE . '(.*?)' . self::AFTER . '@';
		if ( $this->flags->is_case_insensitive() ) {
			$pattern .= 'i';
		}

		if ( \preg_match_all( $pattern, $result, $searches ) > 0 ) {
			return $searches[1];
		}

		return [];
	}

	/**
	 * Globally replace all matches with the content
	 *
	 * @param String $search Search value.
	 * @param String $value Content to replace within.
	 * @return String The `$value` with all the matches replaced
	 */
	public function get_global_replace( $search, $value ) {
		return $this->replace_all( $search, $this->replace, $value );
	}

	/**
	 * Perform a replace on a set of results, optionally with the column and column position specified.
	 * Changes are saved to the database.
	 *
	 * @param Array  $results Array of Result objects.
	 * @param String $column_id The optional column. If specified only this column is replaced.
	 * @param Int    $pos_id The optional position within the column. If specified only this match is replaced.
	 * @return Array|\WP_Error A summary consisting of `rows`, the total number of rows changed, and `phrases` containing how many replacements were made
	 */
	public function save_and_replace( array $results, $column_id = null, $pos_id = null ) {
		$rows_replaced = 0;
		$phrases_replaced = 0;

		foreach ( $results as $result ) {
			$replaced = $this->save_and_replace_result( $result, $column_id, $pos_id );

			if ( \is_wp_error( $replaced ) && is_object( $replaced ) ) {
				return $replaced;
			}

			if ( is_int( $replaced ) && $replaced > 0 ) {
				$rows_replaced++;
				$phrases_replaced += $replaced;
			}
		}

		return [
			'rows' => $rows_replaced,
			'phrases' => $phrases_replaced,
		];
	}

	/**
	 * Perform a replacement for a single result
	 *
	 * @internal
	 * @param Result      $result The result to replace in.
	 * @param String|null $column_id Column ID.
	 * @param Int|null    $pos_id Position within column.
	 * @return Int|\WP_Error Number of phrases replaced, or WP_Error on error
	 */
	private function save_and_replace_result( Result $result, $column_id, $pos_id ) {
		$phrases_replaced = 0;

		foreach ( $result->get_columns() as $column ) {
			if ( ! $this->can_replace_column( $column_id, $column->get_column_id() ) ) {
				continue;
			}

			$replacement = $column->get_replacement( $pos_id, $result->get_raw() );
			if ( $replacement === false ) {
				continue;
			}

			foreach ( $this->sources as $source ) {
				if ( $source->is_type( $result->get_source_type() ) ) {
					$saved = $source->save( $result->get_row_id(), $column->get_column_id(), $replacement );

					if ( is_wp_error( $saved ) && is_object( $saved ) ) {
						return $saved;
					}

					$phrases_replaced += $column->get_match_count();
				}
			}
		}

		return $phrases_replaced;
	}

	/**
	 * Check if we can replace this particular column
	 *
	 * @internal
	 * @param String|null $result_column Column.
	 * @param String      $replace_column Column.
	 * @return boolean
	 */
	private function can_replace_column( $result_column, $replace_column ) {
		return $result_column === null || $result_column === $replace_column;
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
		$pattern = Match::get_pattern( $search, $this->flags );

		// Global replace
		return preg_replace( $pattern, $replace, $value );
	}
}
