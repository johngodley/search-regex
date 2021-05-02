<?php

namespace SearchRegex;

trait Source_HasMeta {
	protected function process_meta( $row_id, $type, array $updates ) {
		foreach ( $updates as $column => $update ) {
			if ( $column === 'meta' ) {
				$this->set_meta( $row_id, $type, $update['change'] );
			}
		}
	}

	private function get_meta( array $meta ) {
		$items = array_map( function( $item ) use ( $meta ) {
			$value = new Match_Context_Value( $meta[ $item ][0] );

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

	private function set_meta( $row_id, $meta_type, $update ) {
		foreach ( $update as $meta ) {
			$key = $meta->get_key();
			$value = $meta->get_value();

			// Delete a meta if we are changing the key, or deleting it
			if ( $key->get_type() === Match_Context_Replace::TYPE_REPLACE || $key->get_type() === Match_Context_Delete::TYPE_DELETE ) {
				$this->log_save( $meta_type . ' meta delete', $row_id . ' = ' . $key->get_value() . ' = ' . $value->get_value() );

				if ( searchregex_can_save() ) {
					delete_meta( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}

			// Update the meta if we are changing the key, or replacing the value
			if ( $value->get_type() === Match_Context_Replace::TYPE_REPLACE || $key->get_type() === Match_Context_Replace::TYPE_REPLACE ) {
				$key_value = $key->get_type() === Match_Context_Replace::TYPE_REPLACE ? $key->get_replacement() : $key->get_value();
				$value_value = $value->get_type() === Match_Context_Replace::TYPE_REPLACE ? $value->get_replacement() : $value->get_value();

				$this->log_save( $meta_type . ' meta update', $row_id . ' = ' . $key_value . ' => ' . $value_value );

				if ( searchregex_can_save() ) {
					update_meta( $meta_type, $row_id, $key_value, $value_value );
				}
			}

			if ( $key->get_type() === Match_Context_Add::TYPE_ADD ) {
				$this->log_save( $meta_type . ' meta add', $row_id . ' = ' . $key->get_value() . ' => ' . $value->get_value() );

				if ( searchregex_can_save() ) {
					update_meta( $meta_type, $row_id, $key->get_value(), $value->get_value() );
				}
			}
		}
	}
}
