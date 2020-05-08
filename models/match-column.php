<?php

namespace SearchRegex;

class Match_Column {
	const CONTEXT_LIMIT = 20;

	/** @var string Column ID */
	private $column_id;
	/** @var string */
	private $column_label;
	/** @var Match_Context[] */
	private $contexts;
	/** @var int */
	private $context_count;
	/** @var int */
	private $match_count;
	/** @var string */
	private $replacement;

	/**
	 * Create a Match Column, which contains an array of Match_Context items for a particular database column.
	 *
	 * @param string          $column_id Column ID.
	 * @param string          $column_label Descriptive column label, shown to the user.
	 * @param string          $replacement The replaced value.
	 * @param Match_Context[] $contexts Array of Match_Context items for this column.
	 */
	public function __construct( $column_id, $column_label, $replacement, array $contexts ) {
		$this->match_count = 0;

		foreach ( $contexts as $context ) {
			$this->match_count += $context->get_match_count();
		}

		$this->column_id = $column_id;
		$this->column_label = $column_label;
		$this->context_count = count( $contexts );
		$this->contexts = array_slice( $contexts, 0, self::CONTEXT_LIMIT );
		$this->replacement = $replacement;
	}

	/**
	 * Convert the Match_Context to JSON
	 *
	 * @return Array JSON data
	 */
	public function to_json() {
		$contexts = [];

		foreach ( $this->contexts as $context ) {
			$contexts[] = $context->to_json();
		}

		return [
			'column_id' => $this->column_id,
			'column_label' => $this->column_label,
			'contexts' => $contexts,
			'context_count' => $this->context_count,
			'match_count' => $this->match_count,
			'replacement' => $this->replacement,  // TODO is this needed?
		];
	}

	/**
	 * Get column ID
	 *
	 * @return String Column ID
	 */
	public function get_column_id() {
		return $this->column_id;
	}

	/**
	 * Get match count
	 *
	 * @return Int Match count
	 */
	public function get_match_count() {
		return $this->match_count;
	}

	/**
	 * Get the replacement
	 *
	 * @param Int|null $pos_id Position within the raw data.
	 * @param Array    $raw Raw column data.
	 * @return Bool|String Replaced data, or false
	 */
	public function get_replacement( $pos_id, array $raw ) {
		if ( $pos_id !== null ) {
			foreach ( $this->contexts as $context ) {
				$match = $context->get_match_at_position( $pos_id );

				if ( is_object( $match ) && isset( $raw[ $this->column_id ] ) ) {
					// Need to replace the match with the result in the raw data
					return $match->replace_at_position( $raw[ $this->column_id ] );
				}
			}

			return false;
		}

		return $this->replacement;
	}
}
