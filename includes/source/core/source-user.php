<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Sql;
use SearchRegex\Plugin;

/**
 * User source
 */
class User extends Source\Source {
	use Source\HasMeta;

	public function get_table_id() {
		return 'ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->users;
	}

	public function get_actions( Search\Result $result ) {
		return [
			'edit' => get_edit_profile_url( $result->get_row_id(), 'admin' ),
		];
	}

	public function get_title_column() {
		return 'user_nicename';
	}

	public function get_row_columns( $row_id ) {
		$meta = $this->get_meta( get_user_meta( $row_id ) );
		$parent = parent::get_row_columns( $row_id );

		if ( $parent instanceof \WP_Error ) {
			return $parent;
		}

		return array_merge(
			$parent,
			count( $meta ) > 0 ? [ $meta ] : []
		);
	}

	public function save( $row_id, array $changes ) {
		$user = $this->get_columns_to_change( $changes );
		$user['ID'] = $row_id;

		$this->process_meta( $row_id, 'user', $changes );

		if ( count( $user ) > 1 ) {
			$this->log_save( 'user', $user );

			$result = true;

			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				$result = wp_update_user( $user );
			}

			if ( $result ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Failed to update user.' );
		}

		return true;
	}

	public function delete_row( $row_id ) {
		$this->log_save( 'delete comment', $row_id );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			if ( wp_delete_user( $row_id ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete user', 401 );
		}

		return true;
	}

	public function autocomplete( array $column, $value ) {
		global $wpdb;

		if ( $column['column'] === 'ID' ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT ID as id,user_login as value FROM {$wpdb->users} WHERE user_login LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		$user_fields = [
			'user_login',
			'user_nicename',
			'display_name',
			'user_email',
		];

		if ( in_array( $column['column'], $user_fields, true ) ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT " . $column['column'] . " as id," . $column['column'] . " as value FROM {$wpdb->users} WHERE " . $column['column'] . " LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		if ( $column['column'] === 'meta' ) {
			return Source\Autocomplete::get_meta( Sql\Value::table( 'usermeta' ), $value );
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		return [
			'name' => __( 'Users', 'search-regex' ),
			'table' => $wpdb->users,
			'columns' => [
				[
					'column' => 'ID',
					'type' => 'integer',
					'title' => __( 'ID', 'search-regex' ),
					'options' => 'api',
					'modify' => false,
				],
				[
					'column' => 'user_login',
					'type' => 'string',
					'title' => __( 'Login', 'search-regex' ),
					'options' => 'api',
					'modify' => false,
				],
				[
					'column' => 'user_nicename',
					'type' => 'string',
					'title' => __( 'Nicename', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'display_name',
					'type' => 'string',
					'title' => __( 'Display Name', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'user_email',
					'type' => 'string',
					'title' => __( 'Email', 'search-regex' ),
					'options' => 'api',
				],
				[
					'column' => 'user_url',
					'type' => 'string',
					'title' => __( 'URL', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'user_registered',
					'type' => 'date',
					'title' => __( 'Registered', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'meta',
					'type' => 'keyvalue',
					'title' => __( 'User meta', 'search-regex' ),
					'options' => 'api',
					'join' => 'usermeta',
				],
			],
		];
	}
}
