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
	 */
	private string $format = 'json';

	/**
	 * Export only the selected columns
	 */
	private bool $selected_only = false;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed>|string  $options Options.
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

	/**
	 * @return array<string, mixed>
	 */
	public function to_json() {
		return [
			'action' => 'export',
			'actionOption' => [
				'format' => $this->format,
				'selectedOnly' => $this->selected_only,
			],
		];
	}

	/**
	 * @return list<string>
	 */
	public function get_view_columns() {
		if ( ! $this->selected_only ) {
			return array_values(
				array_map(
					fn( $column ) => $this->schema->get_type() . '__' . $column->get_column(),
					$this->schema->get_columns()
				)
			);
		}

		return [];
	}

	/**
	 * @return bool
	 */
	public function should_save() {
		return false;
	}

	/**
	 * @param array<string, mixed> $results
	 * @return array<string, mixed>
	 */
	public function get_results( array $results ) {
		if ( ! $this->save ) {
			return parent::get_results( $results );
		}

		// Convert to whatever the chosen format is
		$results['results'] = array_map(
			function ( $item ) {
				if ( $this->format === 'json' ) {
					return $this->convert_to_json( $item );
				} elseif ( $this->format === 'csv' ) {
					return $this->convert_to_csv( $item );
				} elseif ( $this->format === 'sql' ) {
					return $this->convert_to_sql( $item );
				}

				return $item;
			}, $results['results']
		);

		return $results;
	}

	/**
	 * Convert Result to JSON
	 *
	 * @param Search\Result $result Result.
	 * @return array<string, mixed>
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
		$values = array_map(
			function ( $column ) {
				global $wpdb;

				$column_schema = $this->schema->get_column( $column->get_column_id() );

				if ( $column_schema !== null && $column_schema->get_type() === 'integer' ) {
					return $wpdb->prepare( '%d', $column->get_value() );
				}

				return $wpdb->prepare( '%s', $column->get_value() );
			}, $result->get_columns()
		);

		$names = array_map(
			fn( $column ) => Sql\Value::column( $column->get_column_id() )->get_value(),
			$result->get_columns()
		);

		return "INSERT INTO {$this->schema->get_table()} (" . implode( ', ', $names ) . ') VALUES(' . implode( ', ', $values ) . ');';
	}

	/**
	 * Neutralize a value that could be interpreted as a spreadsheet formula when the CSV is opened
	 * in a spreadsheet application. Leading whitespace is trimmed before the check so a formula
	 * hidden behind it is still caught, but the original value is otherwise preserved. Numeric
	 * strings (eg. "-10", "+3.5") are exempt, since a leading +/- there is a sign, not a formula.
	 *
	 * [FORMULA] is used to avoid being removed by spreadsheet applications.
	 *
	 * @param mixed $value Column value.
	 * @return mixed
	 */
	private function sanitise_csv_column( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$trimmed = ltrim( $value );

		if ( $trimmed === '' || ! in_array( $trimmed[0], [ '=', '+', '-', '@' ], true ) ) {
			return $value;
		}

		if ( is_numeric( $trimmed ) ) {
			return $value;
		}

		return '[FORMULA] ' . $value;
	}

	/**
	 * Convert a Result to CSV
	 *
	 * @param Search\Result $result Result.
	 * @return string
	 */
	private function convert_to_csv( Search\Result $result ) {
		$csv = array_map(
			fn( $column ) => $this->sanitise_csv_column( $column->get_value() ),
			$result->get_columns()
		);

		// phpcs:ignore
		$handle = fopen( 'php://memory', 'r+' );

		if ( false === $handle ) {
			return '';
		}

		if ( false === fputcsv( $handle, $csv ) ) {
			// phpcs:ignore
			fclose( $handle );
			return '';
		}

		rewind( $handle );

		$result = stream_get_contents( $handle );

		// phpcs:ignore
		fclose( $handle );

		if ( false === $result ) {
			return '';
		}

		return trim( $result );
	}
}
