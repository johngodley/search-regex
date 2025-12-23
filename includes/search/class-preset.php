<?php

namespace SearchRegex\Search;

use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Filter;

/**
 * @phpstan-type PresetTag array{name: string, title: string}
 * @phpstan-type SearchParams array{
 *     searchPhrase?: string,
 *     replacement?: string,
 *     perPage?: int,
 *     searchFlags?: array<string, mixed>,
 *     source?: list<string>,
 *     action?: string,
 *     filters?: array<string, mixed>,
 *     view?: list<string>
 * }
 * @phpstan-type PresetParams array{
 *     id?: string,
 *     name?: string,
 *     description?: string,
 *     tags?: list<PresetTag>,
 *     locked?: list<string>,
 *     search?: SearchParams,
 * }
 */
class Preset {
	/**
	 * @var string
	 */
	const OPTION_NAME = 'searchregex_presets';

	/**
	 * Preset name
	 */
	private string $name = '';

	/**
	 * Preset ID
	 */
	private string $id;

	/**
	 * Preset description
	 */
	private string $description = '';

	/**
	 * Array of search flags
	 */
	private Flags $search_flags;

	/**
	 * Array of source names
	 *
	 * @var string[]
	 */
	private array $source = [];

	/**
	 * Search phrase
	 */
	private string $search = '';

	/**
	 * Replacement phrase
	 */
	private string $replacement = '';

	/**
	 * Per page
	 */
	private int $per_page = 25;

	/**
	 * Array of tag values
	 *
	 * @var list<array{name: string, title: string}>
	 */
	private array $tags = [];

	/**
	 * Array of locked fields
	 *
	 * @var string[]
	 */
	private array $locked = [];

	/**
	 * Preset action
	 */
	private ?Action\Action $action = null;

	/**
	 * Filters
	 *
	 * @var Filter\Filter[]
	 */
	private array $filters = [];

	/**
	 * View
	 *
	 * @var string[]
	 */
	private array $view = [];

	/**
	 * Create a preset
	 *
	 * @param PresetParams $params Array of params.
	 */
	public function __construct( $params = [] ) {
		$this->id = $params['id'] ?? uniqid();
		$this->search_flags = new Flags();

		$this->set_values( $params );
	}

	/**
	 * Set all the values
	 *
	 * @param PresetParams $params Array of values.
	 * @return void
	 */
	private function set_values( $params ) {
		if ( isset( $params['name'] ) ) {
			$this->name = $this->sanitize( $params['name'] );

			if ( strlen( $this->name ) === 0 ) {
				$this->name = (string) time();
			}
		}

		if ( isset( $params['description'] ) ) {
			$this->description = $this->sanitize( $params['description'] );
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			$this->set_tags( $params['tags'] );
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $params['locked'] ) && is_array( $params['locked'] ) ) {
			$this->set_locked( $params['locked'] );
		}

		$search = $params;
		if ( isset( $params['search'] ) ) {
			$search = $params['search'];
		}

		$this->set_search( $search );
	}

	/**
	 * Set tags
	 *
	 * @param list<PresetTag>|mixed $tags Array of tag values.
	 * @return void
	 */
	private function set_tags( $tags ) {
		$tags = array_map(
			function ( $tag ) {
				if ( ! is_array( $tag ) ) {
					return false;
				}

				$title = isset( $tag['title'] ) ? $tag['title'] : '';
				$name = isset( $tag['name'] ) ? $tag['name'] : '';

				$title = $this->sanitize( $title );
				$name = $this->sanitize( $name );

				if ( $title !== '' && $name !== '' ) {
					return [
						'title' => $title,
						'name' => $name,
					];
				}

				return false;
			}, $tags
		);

		// Unique tags
		$unique_tags = [];
		foreach ( array_filter( $tags, fn( $tag ) => is_array( $tag ) ) as $tag ) {
			if ( isset( $tag['name'] ) ) {
				$unique_tags[ $tag['name'] ] = $tag;
			}
		}

		$this->tags = array_values( $unique_tags );
	}

	/**
	 * Sanitize a displayable string
	 *
	 * @param string $text Text to sanitize.
	 * @return string
	 */
	private function sanitize( $text ) {
		$text = trim( wp_kses( $text, [] ) );
		$text = \html_entity_decode( $text );
		return $text;
	}

	/**
	 * Get allowed search fields
	 *
	 * @return string[]
	 */
	public function get_allowed_fields() {
		return [
			'searchPhrase',
			'replacement',
			'searchFlags',
			'source',
			'sourceFlags',
			'perPage',
			'filters',
			'action',
			'view',
		];
	}

	/**
	 * Set locked
	 *
	 * @param string[] $locked Array of values.
	 * @return void
	 */
	private function set_locked( array $locked ) {
		$this->locked = array_values(
			array_filter(
				$locked, fn( $lock ) => in_array( $lock, $this->get_allowed_fields(), true )
			)
		);
	}

	/**
	 * Set search
	 *
	 * @param SearchParams $search Array of values.
	 * @return void
	 */
	private function set_search( $search ) {
		if ( isset( $search['searchPhrase'] ) ) {
			$this->search = $search['searchPhrase'];
		}

		if ( array_key_exists( 'replacement', $search ) ) {
			$this->replacement = $search['replacement'];
		}

		if ( isset( $search['perPage'] ) ) {
			$this->per_page = min( 5000, max( 25, intval( $search['perPage'], 10 ) ) );
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $search['searchFlags'] ) && is_array( $search['searchFlags'] ) ) {
			$this->search_flags = new Flags( $search['searchFlags'] );
		}

		// Sanitize sources and ensure source flags are allowed by those sources
		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $search['source'] ) && is_array( $search['source'] ) ) {
			$valid_sources = [];
			foreach ( $search['source'] as $source ) {
				$sources = Source\Manager::get( [ $source ], [] );
				if ( $sources ) {
					$valid_sources[] = $source;
				}
			}
			$this->source = $valid_sources;
		}

		$schema = new Schema\Schema( Source\Manager::get_schema( $this->source ) );

		// If there is a replacement then default to global replace, for backwards compatability
		$this->action = new Action\Type\Nothing();

		if ( $this->search !== '' ) {
			$this->action = new Action\Type\Global_Replace(
				[
					'search' => $this->search,
					'replacement' => $this->replacement,
					'flags' => $this->search_flags->to_json(),
				], $schema
			);
		}

		if ( isset( $search['action'] ) ) {
			$this->action = Action\Action::create( $search['action'], Action\Action::get_options( $search ), $schema );
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $search['filters'] ) && is_array( $search['filters'] ) ) {
			$this->filters = Filter\Filter::create( $search['filters'], $schema );
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $search['view'] ) && is_array( $search['view'] ) ) {
			$filtered = [];
			foreach ( $search['view'] as $view ) {
				if ( ! is_string( $view ) ) {
					continue;
				}
				$parts = explode( '__', $view );
				if ( count( $parts ) === 2 ) {
					$filtered[] = $view;
				}
			}
			$this->view = $filtered;
		}
	}

	/**
	 * Update the preset
	 *
	 * @param PresetParams $params New preset values.
	 * @return bool
	 */
	public function update( $params ) {
		$this->set_values( $params );
		$existing = self::get_all();

		foreach ( $existing as $pos => $preset ) {
			if ( isset( $preset['id'] ) && $preset['id'] === $this->id ) {
				$existing[ $pos ] = $this->to_json();
				break;
			}
		}

		return $this->save( $existing );
	}

	/**
	 * Delete the preset
	 *
	 * @return bool
	 */
	public function delete() {
		$existing = self::get_all();
		$existing = array_filter(
			$existing, fn( $preset ) => isset( $preset['id'] ) && $preset['id'] !== $this->id
		);

		return $this->save( $existing );
	}

	/**
	 * Save and create a new preset. Will generate an ID.
	 *
	 * @return bool
	 */
	public function create() {
		$this->id = uniqid();
		$existing = self::get_all();

		// Add to list
		$existing[] = $this->to_json();

		return $this->save( $existing );
	}

	/**
	 * Save the list of presets
	 *
	 * @param PresetParams[] $presets Array of JSON.
	 * @return bool
	 */
	private function save( array $presets ) {
		update_option( self::OPTION_NAME, wp_json_encode( $presets ) );
		return true;
	}

	/**
	 * Get the preset name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Convert the Preset to JSON
	 *
	 * @return PresetParams
	 */
	public function to_json() {
		$search = array_merge(
			[
				'searchPhrase' => $this->search,
				'replacement' => $this->replacement,
				'perPage' => $this->per_page,
				'searchFlags' => $this->search_flags->to_json(),
				'source' => $this->source,
				'filters' => array_map(
					fn( $filter ) => $filter->to_json(), $this->filters
				),
				'view' => $this->view,
			],
			$this->action === null ? [] : $this->action->to_json()
		);

		// Remove replace value if not a global replace
		if ( $search['action'] !== 'replace' ) {
			$search['replacement'] = '';
		}

		return [
			'id' => $this->id,
			'name' => \html_entity_decode( $this->name ),
			'description' => \html_entity_decode( $this->description ),
			'search' => $search,
			'locked' => array_values( $this->locked ),
			'tags' => $this->tags,
		];
	}

	/**
	 * Get all presets as JSON
	 *
	 * @return PresetParams[]
	 */
	public static function get_all() {
		$existing = get_option( self::OPTION_NAME, wp_json_encode( [] ) );
		$existing = json_decode( $existing, true );

		$existing = \array_map(
			function ( $saved ) {
				$search = new Preset( $saved );

				return $search->to_json();
			}, $existing
		);

		sort( $existing );
		return $existing;
	}

	/**
	 * Get a preset by ID
	 *
	 * @param string $id Preset ID.
	 * @return Preset|null
	 */
	public static function get( $id ) {
		$existing = get_option( self::OPTION_NAME, wp_json_encode( [] ) );
		$existing = json_decode( $existing, true );

		foreach ( $existing as $preset ) {
			if ( $preset['id'] === $id ) {
				return new Preset( $preset );
			}
		}

		return null;
	}

	/**
	 * Determine if the preset is valid
	 *
	 * @return boolean
	 */
	public function is_valid() {
		if ( $this->name === '' ) {
			return false;
		}

		return true;
	}

	/**
	 * Import presets from a file
	 *
	 * @param string $filename Filename to import.
	 * @return integer Number of presets imported
	 */
	public static function import( $filename ) {
		// phpcs:ignore
		$file = file_get_contents( $filename );

		if ( $file ) {
			$json = json_decode( $file, true );

			if ( is_array( $json ) ) {
				$imported = 0;

				foreach ( $json as $params ) {
					$preset = new Preset( $params );

					if ( $preset->is_valid() ) {
						$preset->create();
						$imported++;
					}
				}

				return $imported;
			}
		}

		return 0;
	}

	/**
	 * Get the ID of the preset
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}
