<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;
use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Value;

/**
 * User source
 */
class Source_User extends Search_Source {
	use Source_HasMeta;

	public function get_table_id() {
		return 'ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->users;
	}

	public function get_actions( Result $result ) {
		return [
			'edit' => get_edit_profile_url( $result->get_row_id(), 'admin' ),
		];
	}

	public function get_title_column() {
		return 'user_nicename';
	}

	public function get_row_columns( $row_id ) {
		$meta = $this->get_meta( get_user_meta( $row_id ) );

		return array_merge(
			parent::get_row_columns( $row_id ),
			count( $meta ) > 0 ? [ $meta ] : [],
		);
	}

	public function save( $row_id, array $updates ) {
		$user = $this->get_columns_to_change( $updates );
		$user['ID'] = $row_id;

		$this->process_meta( $row_id, 'user', $updates );

		if ( count( $user ) > 1 ) {
			$this->log_save( 'user', $user );

			$result = true;

			if ( searchregex_can_save() ) {
				$result = wp_update_user( $user );
			}

			if ( $result ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Failed to update user.' );
		}
	}

	public function delete_row( $row_id ) {
		$this->log_save( 'delete comment', $row_id );

		if ( searchregex_can_save() ) {
			if ( wp_delete_user( $row_id ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete user', 401 );
		}

		return true;
	}

	public function autocomplete( $column, $value ) {
		global $wpdb;

		if ( $column['column'] === 'ID' ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT ID as id,user_login as value FROM {$wpdb->users} WHERE user_login LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		if ( in_array( $column['column'], [ 'user_login', 'user_nicename', 'display_name', 'user_email' ], true ) ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT " . $column['column'] . " as id," . $column['column'] . " as value FROM {$wpdb->users} WHERE " . $column['column'] . " LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		if ( $column['column'] === 'meta' ) {
			return Autocomplete::get_meta( 'usermeta', $value );
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
