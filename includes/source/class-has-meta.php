<?php

namespace SearchRegex\Source;

use SearchRegex\Context\Type;
use SearchRegex\Plugin;

/**
 * Provides meta table support to sources
 *
 * @phpstan-type MetaItem array{key: string, value: string, value_type: string}
 * @phpstan-type MetaData array{column: string, items: list<MetaItem>}
 */
trait Has_Meta {
	/**
	 * Process meta data in any updates
	 *
	 * @param int $row_id Row ID.
	 * @param string  $type Type of meta data.
	 * @param array<string, mixed>   $updates Array of updates.
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
	 * @param array<string, array<mixed>> $meta Meta data.
	 * @return MetaData
	 */
	private function get_meta( array $meta ) {
		$items = array_map(
			function ( $item ) use ( $meta ) {
				$value = new Type\Value( $meta[ $item ][0] );

				return [
					'key' => $item,
					'value' => $meta[ $item ][0],
					'value_type' => $value->get_value_type(),
				];
			}, array_keys( $meta )
		);

		return [
			'column' => 'meta',
			'items' => $items,
		];
	}

	/**
	 * Set meta data
	 *
	 * @param int $row_id Row ID.
	 * @param string  $meta_type Type of meta data.
	 * @param array<mixed>   $update Array of updates.
	 * @return void
	 */
	private function set_meta( $row_id, $meta_type, $update ) {
		foreach ( $update as $meta ) {
			$key = $meta->get_key();
			$value = $meta->get_value();

			// Delete a meta if we are changing the key, or deleting it
			if ( $key->get_type() === Type\Replace::TYPE_REPLACE || $key->get_type() === Type\Delete::TYPE_DELETE ) {
				$this->log_save( $meta_type . ' meta delete', (string) $row_id . ' = ' . $key->get_value() . ' = ' . $value->get_value() );

				if ( Plugin\Settings::init()->can_save() ) {
					delete_metadata( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}

			// Update the meta if we are changing the key, or replacing the value
			if ( $value->get_type() === Type\Replace::TYPE_REPLACE || $key->get_type() === Type\Replace::TYPE_REPLACE ) {
				$key_value = $key->get_type() === Type\Replace::TYPE_REPLACE ? $key->get_replacement() : $key->get_value();
				$value_value = $value->get_type() === Type\Replace::TYPE_REPLACE ? $value->get_replacement() : $value->get_value();

				$this->log_save( $meta_type . ' meta update', (string) $row_id . ' = ' . $key_value . ' => ' . $value_value );

				if ( Plugin\Settings::init()->can_save() ) {
					update_metadata( $meta_type, $row_id, $key_value, $value_value );
				}
			}

			if ( $key->get_type() === Type\Add::TYPE_ADD ) {
				$this->log_save( $meta_type . ' meta add', (string) $row_id . ' = ' . $key->get_value() . ' => ' . $value->get_value() );

				if ( Plugin\Settings::init()->can_save() ) {
					update_metadata( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}
		}
	}
}
