<?php

namespace SearchRegex\Search;

use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Filter;

/**
 * A saved search
 */
class Preset {
	/**
	 * @var String
	 */
	const OPTION_NAME = 'searchregex_presets';

	/**
	 * Preset name
	 *
	 * @var String
	 */
	private $name = '';

	/**
	 * Preset ID
	 *
	 * @var String
	 */
	private $id;

	/**
	 * Preset description
	 *
	 * @var String
	 */
	private $description = '';

	/**
	 * Array of search flags
	 *
	 * @var Flags
	 */
	private $search_flags;

	/**
	 * Array of source names
	 *
	 * @var String[]
	 */
	private $source = [];

	/**
	 * Search phrase
	 *
	 * @var String
	 */
	private $search = '';

	/**
	 * Replacement phrase
	 *
	 * @var String
	 */
	private $replacement = '';

	/**
	 * Per page
	 *
	 * @var Int
	 */
	private $per_page = 25;

	/**
	 * Array of tag values
	 *
	 * @var list<Array{name: string, title: string}>
	 */
	private $tags = [];

	/**
	 * Array of locked fields
	 *
	 * @var String[]
	 */
	private $locked = [];

	/**
	 * Preset action
	 *
	 * @var Action\Action|null
	 */
	private $action = null;

	/**
	 * Filters
	 *
	 * @var Filter\Filter[]
	 */
	private $filters = [];

	/**
	 * View
	 *
	 * @var string[]
	 */
	private $view = [];

	/**
	 * Create a preset
	 *
	 * @param array $params Array of params.
	 */
	public function __construct( array $params = [] ) {
		$this->id = isset( $params['id'] ) ? $params['id'] : uniqid();
		$this->search_flags = new Flags();

		$this->set_values( $params );
	}

	/**
	 * Set all the values
	 *
	 * @param array $params Array of values.
	 * @return void
	 */
	private function set_values( array $params ) {
		if ( isset( $params['name'] ) ) {
			$this->name = $this->sanitize( $params['name'] );

			if ( strlen( $this->name ) === 0 ) {
				$this->name = (string) time();
			}
		}

		if ( isset( $params['description'] ) ) {
			$this->description = $this->sanitize( $params['description'] );
		}

		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			$this->set_tags( $params['tags'] );
		}

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
	 * @param array $tags Array of values.
	 * @return void
	 */
	private function set_tags( array $tags ) {
		$tags = array_map( function( $tag ) {
			$title = isset( $tag['title'] ) ? $tag['title'] : '';
			$name = isset( $tag['name'] ) ? $tag['name'] : '';

			$title = $this->sanitize( $title );
			$name = $this->sanitize( $name );

			if ( $title !== '' && $tag !== '' ) {
				return [
					'title' => $title,
					'name' => $name,
				];
			}

			return false;
		}, $tags );

		// Unique tags
		$unique_tags = [];
		foreach ( array_filter( $tags ) as $tag ) {
			$unique_tags[ $tag['name'] ] = $tag;
		}

		$this->tags = array_values( $unique_tags );
	}

	/**
	 * Sanitize a displayable string
	 *
	 * @param String $text Text to sanitize.
	 * @return String
	 */
	private function sanitize( $text ) {
		$text = trim( wp_kses( $text, [] ) );
		$text = \html_entity_decode( $text );
		return $text;
	}

	/**
	 * Get allowed search fields
	 *
	 * @return Array
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
	 * @param array $locked Array of values.
	 * @return void
	 */
	private function set_locked( array $locked ) {
		$this->locked = array_filter( $locked, function( $lock ) {
			return in_array( $lock, $this->get_allowed_fields(), true );
		} );
	}

	/**
	 * Set search
	 *
	 * @param array $search Array of values.
	 * @return void
	 */
	private function set_search( array $search ) {
		if ( isset( $search['searchPhrase'] ) ) {
			$this->search = $search['searchPhrase'];
		}

		if ( array_key_exists( 'replacement', $search ) ) {
			$this->replacement = $search['replacement'];
		}

		if ( isset( $search['perPage'] ) ) {
			$this->per_page = min( 5000, max( 25, intval( $search['perPage'], 10 ) ) );
		}

		if ( isset( $search['searchFlags'] ) && is_array( $search['searchFlags'] ) ) {
			$this->search_flags = new Flags( $search['searchFlags'] );
		}

		// Sanitize sources and ensure source flags are allowed by those sources
		if ( isset( $search['source'] ) && is_array( $search['source'] ) ) {
			$sources = array_map( function( $source ) {
				$sources = Source\Manager::get( [ $source ], [] );
				if ( $sources ) {
					return $source;
				}

				return false;
			}, $search['source'] );
			$this->source = array_values( array_filter( $sources ) );
		}

		$schema = new Schema\Schema( Source\Manager::get_schema( $this->source ) );

		// If there is a replacement then default to global replace, for backwards compatability
		$this->action = new Action\Type\Nothing();

		if ( $this->search !== '' ) {
			$this->action = new Action\Type\Global_Replace( [
				'search' => $this->search,
				'replacement' => $this->replacement,
				'flags' => $this->search_flags->to_json(),
			], $schema );
		}

		if ( isset( $search['action'] ) ) {
			$this->action = Action\Action::create( $search['action'], Action\Action::get_options( $search ), $schema );
		}

		if ( isset( $search['filters'] ) && is_array( $search['filters'] ) ) {
			$this->filters = Filter\Filter::create( $search['filters'], $schema );
		}

		if ( isset( $search['view'] ) && is_array( $search['view'] ) ) {
			$this->view = array_values( array_filter( $search['view'], function( $view ) {
				$parts = explode( '__', $view );

				return count( $parts ) === 2;
			} ) );
		}
	}

	/**
	 * Update the preset
	 *
	 * @param array $params New preset values.
	 * @return Bool
	 */
	public function update( array $params ) {
		$this->set_values( $params );
		$existing = self::get_all();

		foreach ( $existing as $pos => $preset ) {
			if ( $preset['id'] === $this->id ) {
				$existing[ $pos ] = $this->to_json();
				break;
			}
		}

		return $this->save( $existing );
	}

	/**
	 * Delete the preset
	 *
	 * @return Bool
	 */
	public function delete() {
		$existing = self::get_all();
		$existing = array_filter( $existing, function( $preset ) {
			return $preset['id'] !== $this->id;
		} );

		return $this->save( $existing );
	}

	/**
	 * Save and create a new preset. Will generate an ID.
	 *
	 * @return Bool
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
	 * @param Array $presets Array of JSON.
	 * @return Bool
	 */
	private function save( array $presets ) {
		update_option( self::OPTION_NAME, wp_json_encode( $presets ) );
		return true;
	}

	/**
	 * Get the preset name
	 *
	 * @return String
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Convert the Preset to JSON
	 *
	 * @return Array
	 */
	public function to_json() {
		$search = array_merge(
			[
				'searchPhrase' => $this->search,
				'replacement' => $this->replacement,
				'perPage' => $this->per_page,
				'searchFlags' => $this->search_flags->to_json(),
				'source' => $this->source,
				'filters' => array_map( function( $filter ) {
					return $filter->to_json();
				}, $this->filters ),
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
			'locked' => $this->locked,
			'tags' => $this->tags,
		];
	}

	/**
	 * Get all presets as JSON
	 *
	 * @return array
	 */
	public static function get_all() {
		$existing = get_option( self::OPTION_NAME, wp_json_encode( [] ) );
		$existing = json_decode( $existing, true );

		$existing = \array_map( function( $saved ) {
			$search = new Preset( $saved );

			return $search->to_json();
		}, $existing );

		sort( $existing );
		return $existing;
	}

	/**
	 * Get a preset by ID
	 *
	 * @param String $id Preset ID.
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
		if ( empty( $this->name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Import presets from a file
	 *
	 * @param String $filename Filename to import.
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
	 * @return String
	 */
	public function get_id() {
		return $this->id;
	}
}
