<?php

namespace SearchRegex\Source;

use SearchRegex\Context\Type;
use SearchRegex\Plugin;

// @phan-file-suppress PhanUndeclaredClassMethod
// @phan-file-suppress PhanUndeclaredClassConstant
// @phan-file-suppress PhanUndeclaredMethod

/**
 * Trait to add term support to a source
 */
trait HasTerms {
	/**
	 * Look for term changes and process them
	 *
	 * @param integer $row_id Row ID.
	 * @param string  $type Type.
	 * @param array   $updates Array of updates.
	 * @return void
	 */
	protected function process_terms( $row_id, $type, array $updates ) {
		foreach ( $updates as $column => $update ) {
			if ( $column === 'category' || $column === 'post_tag' ) {
				$this->set_terms( $row_id, $column, $update );
			}
		}
	}

	/**
	 * Get the terms as an array of value/label
	 *
	 * @param array $terms Terms.
	 * @return array
	 */
	private function get_terms( array $terms ) {
		$cats = [];
		$tags = [];
		$extra = [];

		foreach ( $terms as $term ) {
			if ( $term->taxonomy === 'category' ) {
				$cats[] = [
					'value' => $term->slug,
					'label' => $term->name,
				];
			} else {
				$tags[] = [
					'value' => $term->slug,
					'label' => $term->name,
				];
			}
		}

		if ( count( $tags ) > 0 ) {
			$extra[] = [
				'column' => 'post_tag',
				'items' => $tags,
			];
		}

		if ( count( $cats ) > 0 ) {
			$extra[] = [
				'column' => 'category',
				'items' => $cats,
			];
		}

		return $extra;
	}

	/**
	 * Perform term changes on an object
	 *
	 * @param integer $row_id Row ID.
	 * @param string  $column Column to change.
	 * @param array   $update Changes.
	 * @return void
	 */
	private function set_terms( $row_id, $column, array $update ) {
		// Get all term IDs that haven't changed
		$term_ids = array_map( function( $item ) {
			return intval( $item->get_value(), 10 );
		}, $update['same'] );

		// Get all term IDs that have changed
		foreach ( $update['change'] as $change ) {
			if ( $change->get_type() === Type\Add::TYPE_ADD ) {
				$term_ids[] = intval( $change->get_value(), 10 );
			}
		}

		$this->log_save( 'term ' . $column, $term_ids );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			wp_set_object_terms( $row_id, $term_ids, $column, false );
		}
	}
}
