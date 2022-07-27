<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Sql;
use SearchRegex\Plugin;
use SearchRegex\Filter;

/**
 * Source for posts, pages, and other custom post types
 */
class Post extends Source\Source {
	use Source\HasMeta;
	use Source\HasTerms;

	public function get_name( array $row = [] ) {
		$post_type = isset( $row['post_type'] ) ? $this->get_post_type( $row['post_type'] ) : false;
		if ( $post_type ) {
			return $post_type['label'];
		}

		// Handle post types when it's not registered
		return isset( $row['post_type'] ) ? ucwords( $row['post_type'] ) : $this->source_name;
	}

	/**
	 * Get the custom post type from the source
	 *
	 * @param String $post_type Source post type.
	 * @return null|Array
	 */
	private function get_post_type( $post_type ) {
		$post_types = get_post_types( [], 'objects' );

		foreach ( $post_types as $type ) {
			if ( is_object( $type ) && $type->name === $post_type ) {
				return [
					'value' => $type->name,
					'label' => $type->label,
				];
			}
		}

		return null;
	}

	public function get_actions( Search\Result $result ) {
		$edit = get_edit_post_link( $result->get_row_id(), '' );
		$view = get_permalink( $result->get_row_id() );

		if ( $edit ) {
			return [
				'edit' => $edit,
				'view' => $view,
			];
		}

		return [];
	}

	public function get_info_columns() {
		return [
			new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( $this->get_title_column() ) ),
			new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( 'post_type' ) ),   // We need this to show the 'source'
		];
	}

	public function get_title_column() {
		return 'post_title';
	}

	public function get_table_id() {
		return 'ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->posts;
	}

	public function get_row_columns( $row_id ) {
		$extra = [];
		$meta = $this->get_meta( get_post_meta( $row_id ) );
		if ( count( $meta ) > 0 ) {
			$extra = [ $meta ];
		}

		$object_terms = wp_get_object_terms( $row_id, [ 'post_tag', 'category' ] );
		if ( $object_terms instanceof \WP_Error ) {
			return [];
		}

		$extra = array_merge( $extra, $this->get_terms( $object_terms ) );
		$columns = parent::get_row_columns( $row_id );

		if ( $columns instanceof \WP_Error ) {
			return $columns;
		}

		return array_merge( $columns, $extra );
	}

	public function save( $row_id, array $changes ) {
		$post = $this->get_columns_to_change( $changes );
		$post['ID'] = $row_id;

		$this->process_meta( $row_id, 'post', $changes );
		$this->process_terms( $row_id, 'post', $changes );

		if ( count( $post ) > 1 ) {
			// wp_update_post expects slashes to be present, which are then removed
			if ( isset( $post['post_content'] ) ) {
				$post['post_content'] = wp_slash( $post['post_content'] );
			}

			if ( isset( $post['post_excerpt'] ) ) {
				$post['post_excerpt'] = wp_slash( $post['post_excerpt'] );
			}

			$this->log_save( 'post', $post );

			// This does all the sanitization
			$result = true;

			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				$result = wp_update_post( $post );
			}

			if ( $result ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Failed to update post.' );
		}

		return true;
	}

	public function delete_row( $row_id ) {
		$this->log_save( 'delete post', $row_id );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			if ( wp_delete_post( $row_id, true ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete post', 401 );
		}

		return true;
	}

	public function autocomplete( array $column, $value ) {
		if ( $column['column'] === 'post_author' ) {
			return Source\Autocomplete::get_user( $value );
		}

		if ( $column['column'] === 'post_parent' ) {
			return Source\Autocomplete::get_post( $value, Sql\Value::column( 'ID' ), Sql\Value::column( 'post_title' ) );
		}

		if ( $column['column'] === 'category' ) {
			return Source\Autocomplete::get_category( $value );
		}

		if ( $column['column'] === 'post_tag' ) {
			return Source\Autocomplete::get_tag( $value );
		}

		if ( $column['column'] === 'meta' ) {
			return Source\Autocomplete::get_meta( Sql\Value::table( 'postmeta' ), $value );
		}

		// General text
		if ( in_array( $column['column'], [ 'post_title', 'post_name', 'guid', 'post_mime_type' ], true ) ) {
			return Source\Autocomplete::get_post( $value, Sql\Value::column( $column['column'] ), Sql\Value::column( $column['column'] ) );
		}

		return [];
	}

	/**
	 * Get any preloadable data for the given filter
	 *
	 * @param array         $schema Schema.
	 * @param Filter\Filter $filter Filter.
	 * @return array
	 */
	public function get_filter_preload( $schema, $filter ) {
		/** @psalm-suppress DocblockTypeContradiction */
		if ( $filter instanceof Filter\Type\Filter_Member && ( $schema['column'] === 'category' || $schema['column'] === 'post_tag' ) ) {
			$preload = [];

			foreach ( $filter->get_values() as $value ) {
				$term = get_term( intval( $value, 10 ), $schema['column'] );

				if ( is_object( $term ) && ! $term instanceof \WP_Error ) {
					$preload[] = [
						'label' => $term->name,
						'value' => $schema['column'] . '_' . (string) intval( $value, 10 ),
					];
				}
			}

			return $preload;
		} elseif ( $schema['column'] === 'post_author' && ( $filter instanceof Filter\Type\Filter_Integer || $filter instanceof Filter\Type\Filter_String ) ) {
			$convert = new Source\Convert_Values();

			return [
				[
					'label' => $convert->get_user( '', (string) $filter->get_value() ),
					'value' => $schema['column'] . '_' . (string) $filter->get_value(),
				],
			];
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		$stati = get_post_stati();
		$statuses = [];

		foreach ( array_keys( $stati ) as $status ) {
			$statuses[] = [
				'value' => $status,
				'label' => $status,
			];
		}

		return [
			'name' => __( 'Posts (core & custom)', 'search-regex' ),
			'table' => $wpdb->posts,
			'columns' => [
				[
					'column' => 'ID',
					'type' => 'integer',
					'title' => __( 'ID', 'search-regex' ),
					'modify' => false,
				],
				[
					'column' => 'post_title',
					'type' => 'string',
					'title' => __( 'Title', 'search-regex' ),
					'options' => 'api',
					'global' => true,
					'source' => 'post_title',
				],
				[
					'column' => 'post_name',
					'type' => 'string',
					'title' => __( 'Slug', 'search-regex' ),
					'options' => 'api',
					'length' => 200,
					'global' => true,
				],
				[
					'column' => 'post_type',
					'type' => 'member',
					'options' => $this->get_all_custom_post_types(),
					'title' => __( 'Post Type', 'search-regex' ),
					'source' => 'post_type',
				],
				[
					'column' => 'post_content',
					'type' => 'string',
					'title' => __( 'Content', 'search-regex' ),
					'multiline' => true,
					'global' => true,
				],
				[
					'column' => 'post_author',
					'type' => 'integer',
					'options' => 'api',
					'title' => __( 'Author', 'search-regex' ),
					'source' => 'user',
					'joined_by' => 'user',
				],
				[
					'column' => 'post_date',
					'type' => 'date',
					'title' => __( 'Date', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'post_date_gmt',
					'type' => 'date',
					'title' => __( 'Date GMT', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'post_excerpt',
					'type' => 'string',
					'title' => __( 'Excerpt', 'search-regex' ),
					'multiline' => true,
				],
				[
					'column' => 'post_status',
					'type' => 'member',
					'options' => $statuses,
					'title' => __( 'Post Status', 'search-regex' ),
				],
				[
					'column' => 'comment_status',
					'type' => 'member',
					'options' => [
						[
							'value' => 'open',
							'label' => __( 'Open', 'search-regex' ),
						],
						[
							'value' => 'closed',
							'label' => __( 'Closed', 'search-regex' ),
						],
					],
					'title' => __( 'Comment Status', 'search-regex' ),
					'multiple' => false,
				],
				[
					'column' => 'ping_status',
					'type' => 'member',
					'options' => [
						[
							'value' => 'open',
							'label' => __( 'Open', 'search-regex' ),
						],
						[
							'value' => 'closed',
							'label' => __( 'Closed', 'search-regex' ),
						],
					],
					'title' => __( 'Ping Status', 'search-regex' ),
					'multiple' => false,
				],
				[
					'column' => 'post_password',
					'type' => 'member',
					'options' => [
						[
							'value' => 'password',
							'label' => __( 'Has password', 'search-regex' ),
						],
						[
							'value' => 'nopassword',
							'label' => __( 'Has no password', 'search-regex' ),
						],
					],
					'title' => __( 'Password', 'search-regex' ),
					'multiple' => false,
					'modify' => false,
				],
				[
					'column' => 'post_modified',
					'type' => 'date',
					'title' => __( 'Modified', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'post_modified_gmt',
					'type' => 'date',
					'title' => __( 'Modified GMT', 'search-regex' ),
					'source' => 'date',
				],
				[
					'column' => 'post_parent',
					'type' => 'integer',
					'title' => __( 'Parent', 'search-regex' ),
					'options' => 'api',
					'source' => 'post_title',
					'joined_by' => 'post',
				],
				[
					'column' => 'guid',
					'type' => 'string',
					'title' => __( 'GUID', 'search-regex' ),
					'options' => 'api',
					'length' => 255,
				],
				[
					'column' => 'post_mime_type',
					'type' => 'string',
					'title' => __( 'MIME', 'search-regex' ),
					'options' => 'api',
					'length' => 100,
				],
				[
					'column' => 'comment_count',
					'type' => 'integer',
					'title' => __( 'Comment Count', 'search-regex' ),
					'modify' => false,
				],
				[
					'column' => 'category',
					'type' => 'member',
					'title' => __( 'Post Category', 'search-regex' ),
					'options' => 'api',
					'preload' => true,
					'join' => 'taxonomy',
					'source' => 'term',
				],
				[
					'column' => 'post_tag',
					'type' => 'member',
					'title' => __( 'Post Tag', 'search-regex' ),
					'options' => 'api',
					'preload' => true,
					'join' => 'taxonomy',
					'source' => 'term',
				],
				[
					'column' => 'meta',
					'type' => 'keyvalue',
					'title' => __( 'Post Meta', 'search-regex' ),
					'options' => 'api',
					'join' => 'postmeta',
				],
			],
		];
	}

	/**
	 * Get list of all custom post types that have a label
	 *
	 * @return array
	 */
	private function get_all_custom_post_types() {
		$post_types = get_post_types( [], 'objects' );
		$post_sources = [];

		foreach ( $post_types as $type ) {
			if ( is_object( $type ) && strlen( $type->label ) > 0 ) {
				$post_sources[] = [
					'value' => $type->name,
					'label' => $type->label,
				];
			}
		}

		return apply_filters( 'searchregex_sources_posttype', $post_sources );
	}
}
