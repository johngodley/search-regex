<?php

namespace SearchRegex;

class Action_Modify extends Action {
	private $columns = [];
	private $dynamic_column;

	public function __construct( array $options, Schema $schema ) {
		foreach ( $options as $option ) {
			$source_schema = $schema->get_for_source( isset( $option['source'] ) ? $option['source'] : false );

			if ( $source_schema ) {
				$column = Action_Modify_Column::create( $option, $source_schema );

				if ( $column && $column->is_valid() ) {
					$this->columns[] = $column;
				}
			}
		}

		if ( count( $this->columns ) > 0 ) {
			$this->dynamic_column = new Dynamic_Column();
		}
	}

	public function get_replace_columns() {
		$views = [];

		$remember = function( $return, $tag, $attr, $m ) {
			if ( $tag === 'column' ) {
				if ( isset( $attr['name'] ) ) {
					return 'column::' . $attr['name'] . ' ';
				} elseif ( isset( $attr[0] ) ) {
					return 'column::' . $attr[0] . ' ';
				}
			}

			return '';
		};

		add_filter( 'pre_do_shortcode_tag', $remember, 10, 4 );

		$views = array_map( function( $column ) {
			if ( $column instanceof Action_Modify_String && has_shortcode( $column->get_replace_value(), 'column' ) ) {
				$result = do_shortcode( $column->get_replace_value() );

				if ( preg_match_all( '/column::(.*?)\s/', $result, $matches ) > 0 ) {
					foreach ( $matches[1] as $match ) {
						return $column->get_schema()->get_source() . '__' . $match;
					}
				}
			}

			return false;
		}, $this->columns );

		$views = array_values( array_filter( $views ) );

		remove_filter( 'pre_do_shortcode_tag', $remember, 10, 4 );

		return $views;
	}

	public function to_json() {
		return [
			'action' => 'modify',
			'actionOption' => array_map( function( $column ) {
				return $column->to_json();
			}, $this->columns ),
		];
	}

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		foreach ( $columns as $pos => $column ) {
			foreach ( $this->columns as $action_column ) {
				if ( $source->is_type( $action_column->get_source_name() ) && $action_column->is_for_column( $column->get_column_id() ) ) {
					$columns[ $pos ] = $action_column->perform( $row_id, $action_column->get_row_data( $row ), $source, $column, $row );
					break;
				}
			}
		}

		return $columns;
	}

	public function get_modified_columns() {
		return array_map( function( $column ) {
			return $column->get_source_name() . '__' . $column->get_column_name();
		}, $this->columns );
	}
}

require_once __DIR__ . '/column.php';
