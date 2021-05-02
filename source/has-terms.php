<?php

namespace SearchRegex;

use SearchRegex\Match_Context_Add;

trait Source_HasTerms {
	protected function process_terms( $row_id, $type, array $updates ) {
		foreach ( $updates as $column => $update ) {
			if ( $column === 'category' || $column === 'post_tag' ) {
				$this->set_terms( $row_id, $column, $update );
			}
		}
	}

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

	private function set_terms( $row_id, $column, array $update ) {
		// Get all term IDs that haven't changed
		$term_ids = array_map( function( $item ) {
			return intval( $item->get_value(), 10 );
		}, $update['same'] );

		// Get all term IDs that have changed
		foreach ( $update['change'] as $change ) {
			if ( $change->get_type() === Match_Context_Add::TYPE_ADD ) {
				$term_ids[] = intval( $change->get_value(), 10 );
			}
		}

		$this->log_save( 'term ' . $column, $term_ids );

		if ( searchregex_can_save() ) {
			wp_set_object_terms( $row_id, $term_ids, $column, false );
		}
	}
}
