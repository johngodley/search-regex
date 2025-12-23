<?php

namespace SearchRegex\Cli;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Action;
use SearchRegex\Schema;
use WP_CLI;
use WP_Error;

/**
 * Search Regex WP CLI commands
 */
class Search_Regex_CLI {
	/**
	 * Perform a search using Search Regex
	 *
	 * ## OPTIONS
	 *
	 * <search>
	 * : The search phrase to find
	 *
	 * [--source=<source>]
	 * : The source to search in. Available: posts, comment, user, options, post-meta, comment-meta, user-meta, terms
	 * ---
	 * default: posts
	 * ---
	 *
	 * [--regex]
	 * : Use regular expression search
	 *
	 * [--case-insensitive]
	 * : Perform case-insensitive search
	 *
	 * [--per-page=<number>]
	 * : Number of results per page
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--page=<number>]
	 * : Page number to retrieve
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--limit=<number>]
	 * : Maximum number of results to return (0 for unlimited)
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Search for "hello world" in posts
	 *     $ wp search-regex search "hello world"
	 *
	 *     # Search for a pattern using regex in comments
	 *     $ wp search-regex search "email.*@.*\.com" --source=comment --regex
	 *
	 *     # Case-insensitive search in options
	 *     $ wp search-regex search "siteurl" --source=options --case-insensitive
	 *
	 *     # Get JSON output
	 *     $ wp search-regex search "test" --format=json
	 *
	 *     # Get just the count of matches
	 *     $ wp search-regex search "test" --format=count
	 *
	 * @param array<string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function search( $args, $assoc_args ): void {
		$search_phrase = $args[0];
		$source = $assoc_args['source'] ?? 'posts';
		$per_page = isset( $assoc_args['per-page'] ) ? intval( $assoc_args['per-page'] ) : 25;
		$page = isset( $assoc_args['page'] ) ? intval( $assoc_args['page'] ) : 0;
		$limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 0;
		$format = $assoc_args['format'] ?? 'table';

		// Build search flags
		$search_flags = [];
		if ( isset( $assoc_args['regex'] ) ) {
			$search_flags[] = 'regex';
		}
		if ( isset( $assoc_args['case-insensitive'] ) ) {
			$search_flags[] = 'case';
		}

		// Validate source
		$allowed_sources = Source\Manager::get_all_source_names();
		if ( ! in_array( $source, $allowed_sources, true ) ) {
			WP_CLI::error( sprintf( 'Invalid source "%s". Available sources: %s', $source, implode( ', ', $allowed_sources ) ) );
		}

		WP_CLI::line( sprintf( 'Searching for "%s" in %s...', $search_phrase, $source ) );

		// Perform the search
		$params = [
			'searchPhrase' => $search_phrase,
			'source' => [ $source ],
			'searchFlags' => $search_flags,
			'page' => $page,
			'perPage' => $per_page,
			'limit' => $limit,
			'action' => 'nothing',
			'actionOption' => [],
		];

		try {
			$schema = new Schema\Schema( Source\Manager::get_schema( $params['source'] ) );
			$filters = [];

			// Add global search filter
			if ( $params['searchPhrase'] !== '' ) {
				$filters[] = new Filter\Global_Filter( $params['searchPhrase'], $params['searchFlags'] );
			}

			// Create the action
			$action = Action\Action::create( 'nothing', [], $schema );

			// Get sources
			$sources = Source\Manager::get( $params['source'], $filters );

			// Create the search
			$search = new Search\Search( $sources );

			// Execute search
			$results = $search->get_search_results( $action, $params['page'], $params['perPage'], $params['limit'] );

			if ( is_wp_error( $results ) ) {
				WP_CLI::error( $results->get_error_message() );
				return;
			}

			$result_data = $action->get_results( $results );

			// Handle format
			if ( $format === 'count' ) {
				WP_CLI::line( sprintf( 'Found %d results', count( $result_data['results'] ) ) );
				return;
			}

			if ( count( $result_data['results'] ) === 0 ) {
				WP_CLI::success( 'No results found.' );
				return;
			}

			// Format results for output
			$output_items = [];
			foreach ( $result_data['results'] as $result ) {
				$match_count = 0;
				$columns_text = [];
				$matched_texts = $this->extract_matched_texts( $result );

				if ( isset( $result['columns'] ) ) {
					foreach ( $result['columns'] as $column ) {
						$match_count += isset( $column['match_count'] ) ? intval( $column['match_count'] ) : 0;
						$columns_text[] = $column['column_label'];
					}
				}

				$output_items[] = [
					'row_id' => $result['row_id'],
					'source' => $result['source_name'],
					'title' => $result['title'] ?? '',
					'matches' => $match_count,
					'columns' => implode( ', ', $columns_text ),
					'matched_text' => implode( ' | ', array_unique( $matched_texts ) ),
				];
			}

			// Output results
			if ( $format === 'json' ) {
				$json_output = json_encode( $result_data, JSON_PRETTY_PRINT );
				WP_CLI::line( $json_output !== false ? $json_output : '{}' );
			} else {
				WP_CLI\Utils\format_items( $format, $output_items, [ 'row_id', 'source', 'title', 'matches', 'columns', 'matched_text' ] );
			}

			// Show totals
			if ( isset( $result_data['totals'] ) ) {
				$totals = $result_data['totals'];
				WP_CLI::line( sprintf(
					"\nShowing %d results. Total rows in source: %d",
					count( $result_data['results'] ),
					$totals['rows']
				) );
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( 'Search failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Extract matched text from result data
	 *
	 * @param array<string, mixed> $result Result data.
	 * @return string[] Array of matched text strings.
	 */
	private function extract_matched_texts( $result ) {
		$matched_texts = [];

		if ( isset( $result['columns'] ) ) {
			foreach ( $result['columns'] as $column ) {
				// Extract actual matched text from contexts
				if ( isset( $column['contexts'] ) ) {
					foreach ( $column['contexts'] as $context ) {
						if ( isset( $context['matches'] ) ) {
							foreach ( $context['matches'] as $match ) {
								if ( isset( $match['match'] ) ) {
									$matched_text = $match['match'];
									// Truncate if too long for table display
									if ( strlen( $matched_text ) > 50 ) {
										$matched_text = substr( $matched_text, 0, 47 ) . '...';
									}
									$matched_texts[] = $matched_text;
								}
							}
						}
					}
				}
			}
		}

		return $matched_texts;
	}

	/**
	 * Perform a search and replace using Search Regex
	 *
	 * ## OPTIONS
	 *
	 * <search>
	 * : The search phrase to find
	 *
	 * <replace>
	 * : The replacement text
	 *
	 * [--source=<source>]
	 * : The source to search in. Available: posts, comment, user, options, post-meta, comment-meta, user-meta, terms
	 * ---
	 * default: posts
	 * ---
	 *
	 * [--regex]
	 * : Use regular expression search
	 *
	 * [--case-insensitive]
	 * : Perform case-insensitive search
	 *
	 * [--per-page=<number>]
	 * : Number of results per page to process
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--limit=<number>]
	 * : Maximum number of results to replace (0 for unlimited)
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would be replaced without actually saving changes
	 *
	 * [--format=<format>]
	 * : Output format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Replace "hello world" with "hi everyone" in posts (dry run)
	 *     $ wp search-regex replace "hello world" "hi everyone" --dry-run
	 *
	 *     # Replace using regex in comments
	 *     $ wp search-regex replace "email@.*\.com" "email@example.com" --source=comment --regex
	 *
	 *     # Case-insensitive replace in options and save changes
	 *     $ wp search-regex replace "siteurl" "site_url" --source=options --case-insensitive
	 *
	 *     # Replace with a limit
	 *     $ wp search-regex replace "test" "production" --limit=10
	 *
	 * @param array<string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function replace( $args, $assoc_args ): void {
		if ( count( $args ) < 2 ) {
			WP_CLI::error( 'Both search and replace arguments are required.' );
		}

		$search_phrase = $args[0];
		$replace_phrase = $args[1];
		$source = $assoc_args['source'] ?? 'posts';
		$per_page = isset( $assoc_args['per-page'] ) ? intval( $assoc_args['per-page'] ) : 25;
		$limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 0;
		$format = $assoc_args['format'] ?? 'table';
		$dry_run = isset( $assoc_args['dry-run'] );

		// Build search flags
		$search_flags = [];
		if ( isset( $assoc_args['regex'] ) ) {
			$search_flags[] = 'regex';
		}
		if ( isset( $assoc_args['case-insensitive'] ) ) {
			$search_flags[] = 'case';
		}

		// Validate source
		$allowed_sources = Source\Manager::get_all_source_names();
		if ( ! in_array( $source, $allowed_sources, true ) ) {
			WP_CLI::error( sprintf( 'Invalid source "%s". Available sources: %s', $source, implode( ', ', $allowed_sources ) ) );
		}

		if ( $dry_run ) {
			WP_CLI::line( sprintf( 'DRY RUN: Searching for "%s" to replace with "%s" in %s...', $search_phrase, $replace_phrase, $source ) );
		} else {
			WP_CLI::line( sprintf( 'Replacing "%s" with "%s" in %s...', $search_phrase, $replace_phrase, $source ) );
		}

		// Perform the search and replace
		$params = [
			'searchPhrase' => $search_phrase,
			'replacement' => $replace_phrase,
			'source' => [ $source ],
			'searchFlags' => $search_flags,
			'page' => 0,
			'perPage' => $per_page,
			'limit' => $limit,
			'action' => 'replace',
			'actionOption' => [],
		];

		try {
			$schema = new Schema\Schema( Source\Manager::get_schema( $params['source'] ) );
			$filters = [];

			// Add global search filter
			if ( $params['searchPhrase'] !== '' ) {
				$filters[] = new Filter\Global_Filter( $params['searchPhrase'], $params['searchFlags'] );
			}

			// Create the action
			$action = Action\Action::create(
				'replace',
				[
					'search' => $params['searchPhrase'],
					'replacement' => $params['replacement'],
					'flags' => $params['searchFlags'],
				],
				$schema
			);

			// Set save mode based on dry-run flag
			if ( ! $dry_run ) {
				$action->set_save_mode( true );
			}

			// Get sources
			$sources = Source\Manager::get( $params['source'], $filters );

			// Create the search
			$search = new Search\Search( $sources );

			// Execute search and replace
			$results = $search->get_search_results( $action, $params['page'], $params['perPage'], $params['limit'] );

			if ( is_wp_error( $results ) ) {
				WP_CLI::error( $results->get_error_message() );
				return;
			}

			$result_data = $action->get_results( $results );

			// Handle format
			if ( $format === 'count' ) {
				WP_CLI::line( sprintf( '%s %d results', $dry_run ? 'Would replace' : 'Replaced', count( $result_data['results'] ) ) );
				return;
			}

			if ( count( $result_data['results'] ) === 0 ) {
				WP_CLI::success( 'No results found.' );
				return;
			}

			// Format results for output
			$output_items = [];
			foreach ( $result_data['results'] as $result ) {
				$match_count = 0;
				$columns_text = [];
				$matched_texts = $this->extract_matched_texts( $result );

				if ( isset( $result['columns'] ) ) {
					foreach ( $result['columns'] as $column ) {
						$match_count += isset( $column['match_count'] ) ? intval( $column['match_count'] ) : 0;
						$columns_text[] = $column['column_label'];
					}
				}

				$output_items[] = [
					'row_id' => $result['row_id'],
					'source' => $result['source_name'],
					'title' => $result['title'] ?? '',
					'matches' => $match_count,
					'columns' => implode( ', ', $columns_text ),
					'matched_text' => implode( ' | ', array_unique( $matched_texts ) ),
				];
			}

			// Output results
			if ( $format === 'json' ) {
				$json_output = json_encode( $result_data, JSON_PRETTY_PRINT );
				WP_CLI::line( $json_output !== false ? $json_output : '{}' );
			} else {
				WP_CLI\Utils\format_items( $format, $output_items, [ 'row_id', 'source', 'title', 'matches', 'columns', 'matched_text' ] );
			}

			// Show totals
			if ( isset( $result_data['totals'] ) ) {
				$totals = $result_data['totals'];
				$status_message = sprintf(
					"\n%s %d results. Total rows in source: %d",
					$dry_run ? 'Would replace' : 'Replaced',
					count( $result_data['results'] ),
					$totals['rows']
				);

				if ( $dry_run ) {
					WP_CLI::warning( $status_message . ' (No changes saved - dry run mode)' );
				} else {
					WP_CLI::success( $status_message );
				}
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( 'Replace failed: ' . $e->getMessage() );
		}
	}

	/**
	 * List available search sources
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List all available sources
	 *     $ wp search-regex sources
	 *
	 * @param array<string> $_args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 * @when after_wp_load
	 */
	public function sources( $_args, $assoc_args ): void {
		$format = $assoc_args['format'] ?? 'table';

		$sources = Source\Manager::get_all_sources();
		$output_items = [];

		foreach ( $sources as $source ) {
			$output_items[] = [
				'name' => $source['name'],
				'label' => $source['label'],
				'type' => $source['type'],
			];
		}

		WP_CLI\Utils\format_items( $format, $output_items, [ 'name', 'label', 'type' ] );
	}
}

// Register the WP CLI command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'search-regex', \SearchRegex\Cli\Search_Regex_CLI::class );
}
