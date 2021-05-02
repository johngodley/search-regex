<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Value;

class Action_Export extends Action {
	const ALLOWED_FORMATS = [ 'json', 'csv', 'sql' ];

	private $format = 'json';
	private $table = 'table';
	private $schema = null;
	private $selected_only = false;

	public function __construct( array $options, Schema $schema ) {
		if ( isset( $options['format'] ) && in_array( $options['format'], self::ALLOWED_FORMATS, true ) ) {
			$this->format = $options['format'];
		}

		if ( isset( $options['selectedOnly'] ) && $options['selectedOnly'] ) {
			$this->selected_only = true;
		}

		$this->schema = $schema->get_sources()[0];
	}

	public function to_json() {
		return [
			'action' => 'export',
			'actionOption' => [
				'format' => $this->format,
				'selectedOnly' => $this->selected_only,
			],
		];
	}

	public function get_view_columns() {
		if ( ! $this->selected_only ) {
			return array_map( function( $column ) {
				return $this->schema->get_type() . '__' . $column->get_column();
			}, $this->schema->get_columns() );
		}

		return [];
	}

	// We want this action to return data
	public function should_save() {
		return false;
	}

	// Convert to whatever the chosen format is
	public function get_results( array $results ) {
		$results['results'] = array_map( function( $item ) {
			if ( $this->format === 'json' ) {
				return $this->convert_to_json( $item );
			} else if ( $this->format === 'csv' ) {
				return $this->convert_to_csv( $item );
			} else if ( $this->format === 'sql' ) {
				return $this->convert_to_sql( $item );
			}

			return $item;
		}, $results['results'] );

		return $results;
	}

	private function convert_to_json( Result $result ) {
		$json = $result->to_json();

		// Remove the 'actions' for JSON
		unset( $json['actions'] );

		return $json;
	}

	private function convert_to_sql( Result $result ) {
		$values = array_map( function( $column ) {
			global $wpdb;

			$column_schema = $this->schema->get_column( $column->get_column_id() );

			if ( $column_schema && $column_schema->get_type() === 'integer' ) {
				return $wpdb->prepare( '%d', $column->get_value() );
			}

			return $wpdb->prepare( '%s', $column->get_value() );
		}, $result->get_columns() );

		$names = array_map( function( $column ) {
			return Sql_Value::column( $column->get_column_id() )->get_value();
		}, $result->get_columns() );

		return "INSERT INTO {$this->schema->get_table()} (" . implode( ', ', $names ) . ') VALUES(' . implode( ', ', $values ). ');';
	}

	private function convert_to_csv( Result $result ) {
		$csv = array_map( function( $column ) {
			return $column->get_value();
		}, $result->get_columns() );

		$handle = fopen( 'php://memory', 'r+' );

		fputcsv( $handle, $csv );
		rewind( $handle );

		$result = stream_get_contents( $handle );

		fclose( $handle );

		return trim( $result );
	}
}
