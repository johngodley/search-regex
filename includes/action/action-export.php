<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Sql;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Search;

class Export extends Action\Action {
	const ALLOWED_FORMATS = [ 'json', 'csv', 'sql' ];

	/**
	 * Export format
	 *
	 * @var string
	 */
	private $format = 'json';

	/**
	 * Table being exported
	 *
	 * @var string
	 */
	private $table = 'table';

	/**
	 * Export only the selected columns
	 *
	 * @var boolean
	 */
	private $selected_only = false;

	/**
	 * Constructor
	 *
	 * @param array|string  $options Options.
	 * @param Schema\Schema $schema Schema.
	 */
	public function __construct( $options, Schema\Schema $schema ) {
		if ( isset( $options['format'] ) && in_array( $options['format'], self::ALLOWED_FORMATS, true ) ) {
			$this->format = $options['format'];
		}

		if ( isset( $options['selectedOnly'] ) && $options['selectedOnly'] ) {
			$this->selected_only = true;
		}

		parent::__construct( $options, $schema );
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

	public function should_save() {
		return false;
	}

	public function get_results( array $results ) {
		if ( ! $this->save ) {
			return parent::get_results( $results );
		}

		// Convert to whatever the chosen format is
		$results['results'] = array_map( function( $item ) {
			if ( $this->format === 'json' ) {
				return $this->convert_to_json( $item );
			} elseif ( $this->format === 'csv' ) {
				return $this->convert_to_csv( $item );
			} elseif ( $this->format === 'sql' ) {
				return $this->convert_to_sql( $item );
			}

			return $item;
		}, $results['results'] );

		return $results;
	}

	/**
	 * Convert Result to JSON
	 *
	 * @param Search\Result $result Result.
	 * @return array
	 */
	private function convert_to_json( Search\Result $result ) {
		$data = [];

		foreach ( $result->get_columns() as $column ) {
			$data[ Sql\Value::column( $column->get_column_id() )->get_value() ] = $column->get_value();
		}

		return $data;
	}

	/**
	 * Convert Result to SQL
	 *
	 * @param Search\Result $result Result.
	 * @return string
	 */
	private function convert_to_sql( Search\Result $result ) {
		$values = array_map( function( $column ) {
			global $wpdb;

			$column_schema = $this->schema->get_column( $column->get_column_id() );

			if ( $column_schema && $column_schema->get_type() === 'integer' ) {
				return $wpdb->prepare( '%d', $column->get_value() );
			}

			return $wpdb->prepare( '%s', $column->get_value() );
		}, $result->get_columns() );

		$names = array_map( function( $column ) {
			return Sql\Value::column( $column->get_column_id() )->get_value();
		}, $result->get_columns() );

		return "INSERT INTO {$this->schema->get_table()} (" . implode( ', ', $names ) . ') VALUES(' . implode( ', ', $values ) . ');';
	}

	/**
	 * Convert a Result to CSV
	 *
	 * @param Search\Result $result Result.
	 * @return string
	 */
	private function convert_to_csv( Search\Result $result ) {
		$csv = array_map( function( $column ) {
			return $column->get_value();
		}, $result->get_columns() );

		// phpcs:ignore
		$handle = fopen( 'php://memory', 'r+' );

		fputcsv( $handle, $csv );
		rewind( $handle );

		$result = stream_get_contents( $handle );

		// phpcs:ignore
		fclose( $handle );

		return trim( $result );
	}
}
