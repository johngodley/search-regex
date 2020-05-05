<?php

namespace SearchRegex;

class Match_Column {
	const CONTEXT_LIMIT = 20;

	private $column_id;
	private $column_label;
	private $contexts;
	private $context_count;
	private $match_count;
	private $replacement;

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

	public function get_column_id() {
		return $this->column_id;
	}

	public function get_match_count() {
		return $this->match_count;
	}

	public function get_replacement( $pos_id, $raw ) {
		if ( $pos_id !== null ) {
			foreach ( $this->contexts as $context ) {
				$match = $context->get_match_at_position( $pos_id );

				if ( $match && isset( $raw[ $this->column_id ] ) ) {
					// Need to replace the match with the result in the raw data
					return $match->replace_at_position( $raw[ $this->column_id ] );
				}
			}

			return false;
		}

		return $this->replacement;
	}
}
