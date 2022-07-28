<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Sql;
use SearchRegex\Plugin;

/**
 * Source for comments
 */
class Comment extends Source\Source {
	use Source\HasMeta;

	public function get_actions( Search\Result $result ) {
		$id = $result->get_row_id();
		$link = get_edit_comment_link( $id );
		$comment = get_comment( $id );

		if ( $link && is_object( $comment ) ) {
			$view = get_comment_link( $comment );

			return array_filter( [
				'edit' => str_replace( '&amp;', '&', $link ),
				'view' => $view,
			] );
		}

		return [];
	}

	public function get_table_id() {
		return 'comment_ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->comments;
	}

	public function get_title_column() {
		return 'comment_author';
	}

	public function get_row_columns( $row_id ) {
		$meta = $this->get_meta( get_comment_meta( $row_id ) );
		$row_columns = parent::get_row_columns( $row_id );
		if ( $row_columns instanceof \WP_Error ) {
			return $row_columns;
		}

		return array_merge(
			$row_columns,
			count( $meta ) > 0 ? [ $meta ] : []
		);
	}

	public function save( $row_id, array $changes ) {
		$comment = $this->get_columns_to_change( $changes );
		$comment['ID'] = $row_id;

		$this->process_meta( $row_id, 'comment', $changes );

		if ( count( $comment ) > 1 ) {
			// wp_update_comment expects slashes to be present, which are then removed
			if ( isset( $comment['comment_content'] ) ) {
				$comment['comment_content'] = wp_slash( $comment['comment_content'] );
			}

			$this->log_save( 'comment', $comment );

			$result = true;

			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				$result = wp_update_comment( $comment );
			}

			if ( $result ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Failed to update comment.' );
		}

		return true;
	}

	public function delete_row( $row_id ) {
		$this->log_save( 'delete comment', $row_id );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			if ( wp_delete_comment( $row_id, true ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete comment', 401 );
		}

		return true;
	}

	public function autocomplete( array $column, $value ) {
		global $wpdb;

		if ( ! isset( $column['column'] ) ) {
			return [];
		}

		if ( $column['column'] === 'comment_post_ID' ) {
			return Source\Autocomplete::get_post( $value, Sql\Value::column( 'ID' ), Sql\Value::column( 'post_title' ) );
		}

		if ( $column['column'] === 'user_id' ) {
			return Source\Autocomplete::get_user( $value );
		}

		if ( $column['column'] === 'meta' ) {
			return Source\Autocomplete::get_meta( Sql\Value::table( 'commentmeta' ), $value );
		}

		if ( $column['column'] === 'comment_parent' ) {
			return Source\Autocomplete::get_comment( $value );
		}

		// General text searches
		if ( $column['column'] === 'comment_author' || $column['column'] === 'comment_author_email' ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT " . $column['column'] . " as id," . $column['column'] . " as value FROM {$wpdb->comments} WHERE " . $column['column'] . " LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		return [
			'name' => __( 'Comments', 'search-regex' ),
			'table' => $wpdb->comments,
			'columns' => [
				[
					'column' => 'comment_ID',
					'type' => 'integer',
					'title' => __( 'ID', 'search-regex' ),
					'modify' => false,
				],
				[
					'column' => 'comment_post_ID',
					'type' => 'integer',
					'title' => __( 'Post ID', 'search-regex' ),
					'options' => 'api',
					'joined_by' => 'post',
				],
				[
					'column' => 'comment_author',
					'type' => 'string',
					'title' => __( 'Author', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'comment_author_email',
					'type' => 'string',
					'title' => __( 'Email', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'comment_author_url',
					'type' => 'string',
					'title' => __( 'URL', 'search-regex' ),
					'global' => true,
				],
				[
					'column' => 'comment_content',
					'type' => 'string',
					'title' => __( 'Content', 'search-regex' ),
					'multiline' => true,
					'global' => true,
				],
				[
					'column' => 'comment_author_IP',
					'type' => 'string',
					'title' => __( 'IP', 'search-regex' ),
				],
				[
					'column' => 'comment_date',
					'type' => 'date',
					'title' => __( 'Date', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'comment_date_gmt',
					'type' => 'date',
					'title' => __( 'Date GMT', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'comment_approved',
					'type' => 'member',
					'options' => [
						[
							'value' => '0',
							'label' => __( 'Unapproved', 'search-regex' ),
						],
						[
							'value' => '1',
							'label' => __( 'Approved', 'search-regex' ),
						],
						[
							'value' => 'spam',
							'label' => __( 'Spam', 'search-regex' ),
						],
					],
					'title' => __( 'Approval Status', 'search-regex' ),
				],
				[
					'column' => 'comment_agent',
					'type' => 'string',
					'title' => __( 'User Agent', 'search-regex' ),
				],
				[
					'column' => 'comment_type',
					'type' => 'member',
					'title' => __( 'Type', 'search-regex' ),
					'options' => [
						[
							'value' => 'pingback',
							'label' => __( 'Pingback', 'search-regex' ),
						],
						[
							'value' => 'trackback',
							'label' => __( 'Trackback', 'search-regex' ),
						],
						[
							'value' => 'comment',
							'label' => __( 'Comment', 'search-regex' ),
						],
					],
				],
				[
					'column' => 'comment_parent',
					'type' => 'integer',
					'title' => __( 'Parent Comment', 'search-regex' ),
					'options' => 'api',
				],
				[
					'column' => 'user_id',
					'type' => 'integer',
					'title' => __( 'User ID', 'search-regex' ),
					'options' => 'api',
					'source' => 'user',
					'joined_by' => 'user',
				],
				[
					'column' => 'meta',
					'type' => 'keyvalue',
					'title' => __( 'Comment meta', 'search-regex' ),
					'options' => 'api',
					'join' => 'commentmeta',
				],
			],
		];
	}
}
