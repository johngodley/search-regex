<?php

namespace SearchRegex\Search;

use SearchRegex\Context;

class Column {
	const CONTEXT_LIMIT = 20;

	/**
	 * Column ID
	 *
	 * @readonly
	 * @var string
	 **/
	private $column_id;

	/**
	 * Column label
	 *
	 * @readonly
	 * @var string
	 **/
	private $column_label;

	/**
	 * Array of match contexts
	 *
	 * @var Context\Context[]
	 **/
	private $contexts = [];

	/**
	 * Total number of match contexts
	 *
	 * @var int
	 **/
	private $context_count;

	/**
	 * Total number of matches
	 *
	 * @var int
	 **/
	private $match_count;

	/**
	 * Raw data for this column
	 *
	 * @readonly
	 * @var array
	 */
	private $raw;

	/**
	 * Create a Match Column, which contains an array of Context\Type\Text items for a particular database column.
	 *
	 * @param string                 $column_id Column ID.
	 * @param string                 $column_label Descriptive column label, shown to the user.
	 * @param array<Context\Context> $contexts Contexts.
	 * @param array                  $raw Raw data.
	 */
	public function __construct( $column_id, $column_label, array $contexts, array $raw ) {
		$this->match_count = 0;
		$this->column_id = $column_id;
		$this->column_label = $column_label;
		$this->raw = $raw;
		$this->context_count = 0;

		$this->set_contexts( $contexts );
	}

	/**
	 * Get contexts
	 *
	 * @return array<Context\Context>
	 */
	public function get_contexts() {
		return $this->contexts;
	}

	/**
	 * Add contexts if:
	 * - there are no contexts and it is unmatched
	 * - the contexts match
	 *
	 * The aim is to ensure that we either have all matches, or one unmatched
	 *
	 * @param array<Context\Context> $contexts Contexts.
	 * @return void
	 */
	public function add_contexts_if_matching( array $contexts ) {
		$this->add_contexts( $contexts );

		// Ensure if we have any matches then there are no unmatched
		$matched = array_filter( $this->contexts, function( $context ) {
			return $context->is_matched();
		} );

		if ( count( $matched ) > 0 ) {
			// Remove unmatched
			$this->set_contexts( $matched );
		}
	}

	/**
	 * Add contexts to the column, ensuring we don't have duplicates
	 *
	 * @param array<Context\Context> $contexts Contexts.
	 * @return void
	 */
	public function add_contexts( array $contexts ) {
		foreach ( $contexts as $context ) {
			$found = false;

			// Remove duplicates
			foreach ( $this->contexts as $existing ) {
				if ( $existing->is_equal( $context ) ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$context->set_context_id( count( $this->contexts ) );

				$this->contexts[] = $context;
				$this->match_count += $context->get_match_count();
			}
		}

		$this->context_count = count( $this->contexts );
		$this->contexts = array_slice( $this->contexts, 0, self::CONTEXT_LIMIT );
	}

	/**
	 * Set the context array
	 *
	 * @param Context\Context[] $contexts Array of contexts.
	 * @return void
	 */
	public function set_contexts( array $contexts ) {
		$this->contexts = [];
		$this->add_contexts( $contexts );
	}

	/**
	 * Convert the Context\Type\Text to JSON
	 *
	 * @return Array{column_id: string, column_label: string, contexts: array, context_count: int, match_count: int} JSON data
	 */
	public function to_json() {
		return [
			'column_id' => $this->column_id,
			'column_label' => $this->column_label,
			'contexts' => array_map( function( $item ) {
				return $item->to_json();
			}, $this->contexts ),
			'context_count' => $this->context_count,
			'match_count' => $this->match_count,
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
	 * Get contexts that have changed
	 *
	 * @param array $raw Raw data.
	 * @return array<Context\Context>
	 */
	public function get_changes( array $raw ) {
		return array_values( array_filter( $this->contexts, function( $context ) {
			return $context->needs_saving();
		} ) );
	}

	/**
	 * Get contexts that have not changed
	 *
	 * @param array $raw Raw data.
	 * @return array<Context\Context>
	 */
	public function get_same( array $raw ) {
		return array_values( array_filter( $this->contexts, function( $context ) {
			return ! $context->needs_saving();
		} ) );
	}

	/**
	 * Get the value for this column
	 *
	 * @return string
	 */
	public function get_value() {
		return array_values( $this->raw )[0];
	}
}
