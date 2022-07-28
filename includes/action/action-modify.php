<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Action;
use SearchRegex\Source;
use SearchRegex\Schema;
use SearchRegex\Modifier;

/**
 * Perform modification of columns
 */
class Modify extends Action\Action {
	/**
	 * Columns that are to be modified
	 *
	 * @var Modifier\Modifier[]
	 */
	private $columns = [];

	/**
	 * Dynamic shortcode handler
	 *
	 * @var Action\Dynamic_Column|null
	 */
	private $dynamic_column = null;

	/**
	 * Constructor
	 *
	 * @param array|string  $options Options.
	 * @param Schema\Schema $schema Schema.
	 */
	public function __construct( $options, Schema\Schema $schema ) {
		if ( is_array( $options ) ) {
			foreach ( $options as $option ) {
				$source_schema = $schema->get_for_source( isset( $option['source'] ) ? $option['source'] : '' );

				if ( $source_schema ) {
					$column = Modifier\Modifier::create( $option, $source_schema );

					if ( $column && $column->is_valid() ) {
						$this->columns[] = $column;
					}
				}
			}
		}

		if ( count( $this->columns ) > 0 ) {
			$this->dynamic_column = new Action\Dynamic_Column();
		}

		parent::__construct( $options, $schema );
	}

	public function get_view_columns() {
		/** @psalm-suppress UnusedClosureParam */
		$remember = function( $return, $tag, $attr ) {
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
			if ( ! $column instanceof Modifier\Value\String_Value ) {
				return false;
			}

			$replace = $column->get_replace_value();
			if ( $replace === null ) {
				return false;
			}

			if ( has_shortcode( $replace, 'column' ) ) {
				$result = do_shortcode( $replace );

				if ( preg_match_all( '/column::(.*?)\s/', $result, $matches ) > 0 ) {
					foreach ( $matches[1] as $match ) {
						return $column->get_schema()->get_source() . '__' . $match;
					}
				}
			}

			return false;
		}, $this->columns );

		remove_filter( 'pre_do_shortcode_tag', $remember, 10 );

		$modify = array_map( function( $column ) {
			return $column->get_source_name() . '__' . $column->get_column_name();
		}, $this->columns );

		return array_values( array_filter( array_merge( $views, $modify ) ) );
	}

	public function to_json() {
		return [
			'action' => 'modify',
			'actionOption' => array_map( function( $column ) {
				return $column->to_json();
			}, $this->columns ),
		];
	}

	public function perform( $row_id, array $row, Source\Source $source, array $columns ) {
		foreach ( $columns as $pos => $column ) {
			foreach ( $this->columns as $action_column ) {
				if ( $source->is_type( $action_column->get_source_name() ) && $action_column->is_for_column( $column->get_column_id() ) ) {
					$value = $action_column->get_row_data( $row );

					if ( $value ) {
						$columns[ $pos ] = $action_column->perform( $row_id, $value, $source, $column, $row, $this->should_save() );
					}

					break;
				}
			}
		}

		return $columns;
	}
}
