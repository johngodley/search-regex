<?php

namespace SearchRegex\Source;

use SearchRegex\Context\Type;
use SearchRegex\Plugin;

// @phan-file-suppress PhanUndeclaredClassMethod
// @phan-file-suppress PhanUndeclaredClassConstant
// @phan-file-suppress PhanUndeclaredMethod

/**
 * Provides meta table support to sources
 */
trait HasMeta {
	/**
	 * Process meta data in any updates
	 *
	 * @param integer $row_id Row ID.
	 * @param string  $type Type of meta data.
	 * @param array   $updates Array of updates.
	 * @return void
	 */
	protected function process_meta( $row_id, $type, array $updates ) {
		foreach ( $updates as $column => $update ) {
			if ( $column === 'meta' ) {
				$this->set_meta( $row_id, $type, $update['change'] );
			}
		}
	}

	/**
	 * Get meta data
	 *
	 * @param array $meta Meta data.
	 * @return array
	 */
	private function get_meta( array $meta ) {
		$items = array_map( function( $item ) use ( $meta ) {
			$value = new Type\Value( $meta[ $item ][0] );

			return [
				'key' => $item,
				'value' => $meta[ $item ][0],
				'value_type' => $value->get_value_type(),
			];
		}, array_keys( $meta ) );

		return [
			'column' => 'meta',
			'items' => $items,
		];
	}

	/**
	 * Set meta data
	 *
	 * @param integer $row_id Row ID.
	 * @param string  $meta_type Type of meta data.
	 * @param array   $update Array of updates.
	 * @return void
	 */
	private function set_meta( $row_id, $meta_type, $update ) {
		foreach ( $update as $meta ) {
			$key = $meta->get_key();
			$value = $meta->get_value();

			// Delete a meta if we are changing the key, or deleting it
			if ( $key->get_type() === Type\Replace::TYPE_REPLACE || $key->get_type() === Type\Delete::TYPE_DELETE ) {
				/** @suppress PhanUndeclaredMethod */
				/** @suppress PhanUndeclaredClassMethod */
				$this->log_save( $meta_type . ' meta delete', (string) $row_id . ' = ' . $key->get_value() . ' = ' . $value->get_value() );

				/** @psalm-suppress UndefinedFunction */
				if ( Plugin\Settings::init()->can_save() ) {
					delete_metadata( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}

			// Update the meta if we are changing the key, or replacing the value
			if ( $value->get_type() === Type\Replace::TYPE_REPLACE || $key->get_type() === Type\Replace::TYPE_REPLACE ) {
				$key_value = $key->get_type() === Type\Replace::TYPE_REPLACE ? $key->get_replacement() : $key->get_value();
				$value_value = $value->get_type() === Type\Replace::TYPE_REPLACE ? $value->get_replacement() : $value->get_value();

				/** @suppress PhanUndeclaredMethod */
				$this->log_save( $meta_type . ' meta update', (string) $row_id . ' = ' . $key_value . ' => ' . $value_value );

				/** @psalm-suppress UndefinedFunction */
				if ( Plugin\Settings::init()->can_save() ) {
					update_metadata( $meta_type, $row_id, $key_value, $value_value );
				}
			}

			if ( $key->get_type() === Type\Add::TYPE_ADD ) {
				/** @suppress PhanUndeclaredMethod */
				$this->log_save( $meta_type . ' meta add', (string) $row_id . ' = ' . $key->get_value() . ' => ' . $value->get_value() );

				/** @psalm-suppress UndefinedFunction */
				if ( Plugin\Settings::init()->can_save() ) {
					update_metadata( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}
		}
	}
}
