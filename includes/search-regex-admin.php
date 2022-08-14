<?php

namespace SearchRegex\Admin;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Plugin;

require_once __DIR__ . '/search/class-search.php';
require_once __DIR__ . '/filter/class-search-filter.php';
require_once __DIR__ . '/modifier/class-modifier.php';
require_once __DIR__ . '/source/class-source.php';
require_once __DIR__ . '/schema/class-schema.php';
require_once __DIR__ . '/context/class-context.php';
require_once __DIR__ . '/action/class-action.php';
require_once __DIR__ . '/sql/class-sql.php';

class Admin {
	/** @var null|Admin */
	private static $instance = null;

	/**
	 * Initialize the object
	 *
	 * @return Admin
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create the admin class
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'plugin_action_links_' . basename( dirname( SEARCHREGEX_FILE ) ) . '/' . basename( SEARCHREGEX_FILE ), [ $this, 'plugin_settings' ], 10, 4 );
		add_filter( 'searchregex_result_actions', [ $this, 'extra_actions' ], 10, 3 );
		add_filter( 'load_script_translation_file', [ $this, 'load_script_translation_file' ], 10, 3 );

		register_uninstall_hook( SEARCHREGEX_FILE, [ '\SearchRegex\Admin\Admin', 'plugin_uninstall' ] );
	}

	/*
	 * Massage the Search Regex WP translations.
	 */
	public function load_script_translation_file( $file, $handle, $domain ) {
		if ( $domain === 'search-regex' ) {
			return preg_replace( '/-\w*\./', '.', $file );
		}

		return $file;
	}

	/**
	 * Tidy the plugin up after being uninstalled
	 *
	 * @return void
	 */
	public static function plugin_uninstall() {
		/** @psalm-suppress UndefinedConstant */
		Plugin\Settings::init()->delete();
		delete_option( Search\Preset::OPTION_NAME );
	}

	/**
	 * Plugin is activated. Load the presets file
	 *
	 * @return void
	 */
	public static function plugin_activated() {
	}

	/**
	 * Add plugin settings to plugin page
	 *
	 * @param Array $links Links.
	 * @return Array
	 */
	public function plugin_settings( $links ) {
		array_unshift( $links, '<a href="' . esc_url( $this->get_plugin_url() ) . '&amp;sub=options">' . __( 'Settings', 'search-regex' ) . '</a>' );
		return $links;
	}

	/**
	 * Get plugin URL
	 *
	 * @return String
	 */
	private function get_plugin_url() {
		return admin_url( 'tools.php?page=' . basename( SEARCHREGEX_FILE ) );
	}

	/**
	 * Get first page the current user is allowed to see
	 *
	 * @return String
	 */
	private function get_first_available_page_url() {
		/** @psalm-suppress UndefinedClass */
		$pages = Plugin\Capabilities::get_available_pages();

		if ( count( $pages ) > 0 ) {
			return $this->get_plugin_url() . ( $pages[0] === 'search' ? '' : '&sub=' . rawurlencode( $pages[0] ) );
		}

		return admin_url();
	}

	/**
	 * Insert stuff into the admin head
	 *
	 * @return void
	 */
	public function searchregex_head() {
		global $wp_version;

		// Does user have access to this page?
		if ( $this->get_current_page() === false ) {
			// Redirect to root plugin page
			wp_safe_redirect( $this->get_first_available_page_url() );
			die();
		}

		if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) ) {
			if ( $_REQUEST['action'] === 'rest_api' ) {
				$this->set_rest_api( intval( $_REQUEST['rest_api'], 10 ) );
			}
		}

		$build = SEARCHREGEX_VERSION . '-' . SEARCHREGEX_BUILD;
		$preload = $this->get_preload_data();
		$settings = Plugin\Settings::init();

		$versions = array(
			'Plugin: ' . SEARCHREGEX_VERSION,
			'WordPress: ' . $wp_version . ' (' . ( is_multisite() ? 'multi' : 'single' ) . ')',
			'PHP: ' . phpversion() . ' ' . ini_get( 'memory_limit' ) . ' ' . ini_get( 'max_execution_time' ) . 's',
			'Browser: ' . $this->get_user_agent(),
			'JavaScript: ' . plugin_dir_url( SEARCHREGEX_FILE ) . 'search-regex.js',
			'REST API: ' . $settings->get_rest_api_url(),
		);

		wp_enqueue_script( 'search-regex', plugin_dir_url( SEARCHREGEX_FILE ) . 'build/search-regex.js', [], $build, true );
		wp_enqueue_style( 'search-regex', plugin_dir_url( SEARCHREGEX_FILE ) . 'build/search-regex.css', [], $build );

		/** @psalm-suppress UndefinedClass */
		$pages = Plugin\Capabilities::get_available_pages();

		/** @psalm-suppress UndefinedClass */
		$caps = Plugin\Capabilities::get_all_capabilities();

		$is_new = false;
		$major_version = implode( '.', array_slice( explode( '.', SEARCHREGEX_VERSION ), 0, 2 ) );

		// phpcs:ignore
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'search-regex.php' && strpos( SEARCHREGEX_VERSION, '-beta' ) === false ) {
			$is_new = $settings->is_new_version( $major_version );
		}

		wp_localize_script( 'search-regex', 'SearchRegexi10n', array(
			'api' => [
				'WP_API_root' => esc_url_raw( $settings->get_rest_api_url() ),
				'WP_API_nonce' => wp_create_nonce( 'wp_rest' ),
				'site_health' => admin_url( 'site-health.php' ),
				'current' => $settings->get_rest_api(),
				'routes' => $settings->get_available_rest_api(),
			],
			'pluginBaseUrl' => plugins_url( '', SEARCHREGEX_FILE ),
			'pluginRoot' => $this->get_plugin_url(),
			'locale' => implode( '-', array_slice( explode( '-', str_replace( '_', '-', get_locale() ) ), 0, 2 ) ),
			'settings' => $settings->get_as_json(),
			'preload' => $preload,
			'versions' => implode( "\n", $versions ),
			'version' => SEARCHREGEX_VERSION,
			'caps' => [
				'pages' => $pages,
				'capabilities' => $caps,
			],
			'update_notice' => $is_new ? $major_version : false,
		) );

		wp_set_script_translations( 'search-regex', 'search-regex', plugin_dir_path( SEARCHREGEX_FILE ) . 'languages/json/' );

		$this->add_help_tab();
	}

	/**
	 * Get browser agent
	 *
	 * @return String
	 */
	public function get_user_agent() {
		$agent = '';

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$agent = $_SERVER['HTTP_USER_AGENT'];
		}

		return $agent;
	}

	/**
	 * Set REST API
	 *
	 * @param Int $api API.
	 * @return void
	 */
	private function set_rest_api( $api ) {
		Plugin\Settings::init()->set_rest_api( intval( $api, 10 ) );
	}

	/**
	 * Get preloaded data
	 *
	 * @return Array
	 */
	private function get_preload_data() {
		$schema = Source\Manager::get_schema();
		$presets = Search\Preset::get_all();

		return [
			'sources' => Source\Manager::get_all_grouped(),
			'presets' => $presets,
			'schema' => $schema,
			'labels' => $this->get_preload_labels( $presets ),
		];
	}

	/**
	 * Get the preloaded labels.
	 *
	 * @param array $presets
	 * @return array
	 */
	private function get_preload_labels( array $presets ) {
		$preload = [];
		$filters_to_preload = [];

		if ( isset( $_GET['filters'] ) ) {
			$filters = json_decode( stripslashes( $_GET['filters'] ), true );
			$filters_to_preload = array_merge( $filters_to_preload, $filters );
		}

		foreach ( $presets as $preset ) {
			if ( isset( $preset['search']['filters'] ) ) {
				$filters_to_preload = array_merge( $filters_to_preload, $preset['search']['filters'] );
			}

			if ( isset( $preset['search']['action'] ) && $preset['search']['action'] === 'modify' && is_array( $preset['search']['actionOption'] ) ) {
				$filters_to_preload = array_merge( $filters_to_preload, array_map( function( $action ) {
					return [
						'type' => $action['source'],
						'items' => [
							array_merge(
								[ 'column' => $action['column'] ],
								$action
							),
						],
					];
				}, $preset['search']['actionOption'] ) );
			}
		}

		foreach ( $filters_to_preload as $filter ) {
			if ( is_array( $filter ) && isset( $filter['type'] ) && isset( $filter['items'] ) ) {
				foreach ( $filter['items'] as $filt ) {
					$preload = array_merge( $preload, Source\Manager::get_schema_preload( $filter['type'], $filt ) );
				}
			}
		}

		return array_values( array_filter( $preload ) );
	}

	/**
	 * Replace links
	 *
	 * @internal
	 * @param String $text Text to replace.
	 * @param String $url URL to insert into the link.
	 * @param String $link Link name.
	 * @return String
	 */
	private function linkify( $text, $url, $link = 'link' ) {
		return preg_replace( '@{{' . $link . '}}(.*?){{/' . $link . '}}@', '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">$1</a>', $text );
	}

	/**
	 * Add help tab
	 *
	 * @internal
	 * @return void
	 */
	private function add_help_tab() {
		$flags = $this->linkify(
			$this->linkify(
				__( '{{link}}Source Flags{{/link}} - additional options for the selected source. For example, include post {{guid}}GUID{{/guid}} in the search.', 'search-regex' ),
				'https://searchregex.com/support/search-source/'
			),
			'https://deliciousbrains.com/wordpress-post-guids-sometimes-update/',
			'guid'
		);

		/* translators: URL */
		$content = [ '<p>' . sprintf( __( 'You can find full documentation about using Search Regex on the <a href="%s" target="_blank">searchregex.com</a> support site.', 'search-regex' ), 'https://searchregex.com/support/' ) . '</p>' ];
		$content[] = '<p>' . __( 'The following concepts are used by Search Regex:', 'search-regex' ) . '</p>';
		$content[] = '<ul>';
		$content[] = '<li>' . $this->linkify( __( '{{link}}Search Flags{{/link}} - additional qualifiers for your search, to enable case insensitivity, and to enable regular expression support.', 'search-regex' ), 'https://searchregex.com/support/searching/' ) . '</li>';
		$content[] = '<li>' . $this->linkify( __( '{{link}}Regular expression{{/link}} - a way of defining a pattern for text matching. Provides more advanced matches.', 'search-regex' ), 'https://searchregex.com/support/regular-expression/' ) . '</li>';
		$content[] = '<li>' . $this->linkify( __( '{{link}}Source{{/link}} - the source of data you wish to search. For example, posts, pages, or comments.', 'search-regex' ), 'https://searchregex.com/support/search-source/' ) . '</li>';
		$content[] = '<li>' . $flags . '</li>';
		$content[] = '</ul>';

		$title = __( 'Search Regex Support', 'search-regex' );

		$current_screen = get_current_screen();
		if ( $current_screen ) {
			$current_screen->add_help_tab( array(
				'id'        => 'search-regex',
				'title'     => 'Search Regex',
				'content'   => "<h2>$title</h2>" . implode( "\n", $content ),
			) );
		}
	}

	/**
	 * Get i18n data
	 *
	 * @internal
	 * @return Array
	 */
	private function get_i18n_data() {
		$locale = get_locale();

		// WP 4.7
		if ( function_exists( 'get_user_locale' ) ) {
			$locale = get_user_locale();
		}

		$i18n_json = dirname( SEARCHREGEX_FILE ) . '/locale/json/search-regex-' . $locale . '.json';

		if ( is_file( $i18n_json ) && is_readable( $i18n_json ) ) {
			// phpcs:ignore
			$locale_data = @file_get_contents( $i18n_json );

			if ( $locale_data ) {
				return json_decode( $locale_data, true );
			}
		}

		// Return empty if we have nothing to return so it doesn't fail when parsed in JS
		return array();
	}

	/**
	 * Admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		/** @psalm-suppress UndefinedClass */
		$access = Plugin\Capabilities::get_plugin_access();
		$hook = add_management_page( 'Search Regex', 'Search Regex', $access, basename( SEARCHREGEX_FILE ), [ $this, 'admin_screen' ] );
		if ( $hook ) {
			add_action( 'load-' . $hook, [ $this, 'searchregex_head' ] );
		}
	}

	/**
	 * Check if we meet minimum WP requirements
	 *
	 * @return Bool
	 */
	private function check_minimum_wp() {
		$wp_version = get_bloginfo( 'version' );

		if ( version_compare( $wp_version, SEARCHREGEX_MIN_WP, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Admin screen
	 *
	 * @return void
	 */
	public function admin_screen() {
		/** @psalm-suppress UndefinedClass */
		if ( count( Plugin\Capabilities::get_all_capabilities() ) === 0 ) {
			die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( $this->check_minimum_wp() === false ) {
			$this->show_minimum_wordpress();
			return;
		}

		$this->show_main();
	}

	/**
	 * Show minimum supported WP
	 *
	 * @internal
	 * @return void
	 */
	private function show_minimum_wordpress() {
		global $wp_version;

		/* translators: 1: Expected WordPress version, 2: Actual WordPress version */
		$wp_requirement = sprintf( __( 'Search Regex requires WordPress v%1$1s, you are using v%2$2s - please update your WordPress', 'search-regex' ), SEARCHREGEX_MIN_WP, $wp_version );
		?>
	<div class="react-error">
		<h1><?php esc_html_e( 'Unable to load Search Regex', 'search-regex' ); ?></h1>
		<p><?php echo esc_html( $wp_requirement ); ?></p>
	</div>
		<?php
	}

	/**
	 * Show fail to load page
	 *
	 * @internal
	 * @return void
	 */
	private function show_load_fail() {
		?>
	<div class="react-error" style="display: none">
		<h1><?php esc_html_e( 'Unable to load Search Regex ☹️', 'search-regex' ); ?> v<?php echo esc_html( SEARCHREGEX_VERSION ); ?></h1>
		<p><?php esc_html_e( "This may be caused by another plugin - look at your browser's error console for more details.", 'search-regex' ); ?></p>
		<p><?php esc_html_e( 'If you are using a page caching plugin or service (CloudFlare, OVH, etc) then you can also try clearing that cache.', 'search-regex' ); ?></p>
		<p><?php _e( 'Also check if your browser is able to load <code>search-regex.js</code>:', 'search-regex' ); ?></p>
		<p><code><?php echo esc_html( plugin_dir_url( SEARCHREGEX_FILE ) . 'search-regex.js?ver=' . rawurlencode( SEARCHREGEX_VERSION ) . '-' . rawurlencode( SEARCHREGEX_BUILD ) ); ?></code></p>
		<p><?php esc_html_e( 'Please note that Search Regex requires the WordPress REST API to be enabled. If you have disabled this then you won\'t be able to use Search Regex', 'search-regex' ); ?></p>
		<p><?php _e( 'Please see the <a href="https://searchregex.com/support/problems/">list of common problems</a>.', 'search-regex' ); ?></p>
		<p><?php esc_html_e( 'If you think Search Regex is at fault then create an issue.', 'search-regex' ); ?></p>
		<p class="versions"><?php _e( '<code>SearchRegexi10n</code> is not defined. This usually means another plugin is blocking Search Regex from loading. Please disable all plugins and try again.', 'search-regex' ); ?></p>
		<p>
			<a class="button-primary" target="_blank" href="https://github.com/johngodley/search-regex/issues/new?title=Problem%20starting%20Search%20Regex%20<?php echo esc_attr( SEARCHREGEX_VERSION ); ?>">
				<?php esc_html_e( 'Create Issue', 'search-regex' ); ?>
			</a>
		</p>
	</div>
		<?php
	}

	/**
	 * Show main UI
	 *
	 * @internal
	 * @return void
	 */
	private function show_main() {
		?>
	<div id="react-ui">
		<div class="react-loading">
			<h1><?php esc_html_e( 'Loading, please wait...', 'search-regex' ); ?></h1>

			<span class="react-loading-spinner"></span>
		</div>
		<noscript><?php esc_html_e( 'Please enable JavaScript', 'search-regex' ); ?></noscript>

		<?php $this->show_load_fail(); ?>
	</div>

	<script>
		var prevError = window.onerror;
		var errors = [];
		var timeout = 0;
		var timer = setInterval( function() {
			if ( isSearchRegexLoaded() ) {
				resetAll();
			} else if ( errors.length > 0 || timeout++ === 5 ) {
				showError();
			}
		}, 5000 );

		function isSearchRegexLoaded() {
			return typeof searchregex !== 'undefined';
		}

		function showError() {
			var errorText = "";

			if ( errors.length > 0 ) {
				errorText = "```\n" + errors.join( ',' ) + "\n```\n\n";
			}

			resetAll();
			document.querySelector( '.react-loading' ).style.display = 'none';
			document.querySelector( '.react-error' ).style.display = 'block';

			if ( typeof SearchRegexi10n !== 'undefined' ) {
				document.querySelector( '.versions' ).innerHTML = SearchRegexi10n.versions.replace( /\n/g, '<br />' );
				document.querySelector( '.react-error .button-primary' ).href += '&body=' + encodeURIComponent( errorText ) + encodeURIComponent( SearchRegexi10n.versions );
			}
		}

		function resetAll() {
			clearInterval( timer );
			window.onerror = prevError;
		}

		window.onerror = function( error, url, line ) {
			console.error( error );
			errors.push( error + ' ' + url + ' ' + line );
		};
	</script>
		<?php
	}

	/**
	 * Get the current plugin page.
	 * Uses $_GET['sub'] to determine the current page unless a page is supplied.
	 *
	 * @param String|Bool $page Current page.
	 *
	 * @return string|boolean Current page, or false.
	 */
	private function get_current_page( $page = false ) {
		// $_GET['sub'] is validated below
		// phpcs:ignore
		if ( ! $page ) {
			// phpcs:ignore
			$page = isset( $_GET['sub'] ) ? $_GET['sub'] : 'search';
		}

		// Are we allowed to access this page?
		/** @psalm-suppress UndefinedClass */
		if ( in_array( $page, Plugin\Capabilities::get_available_pages(), true ) ) {
			// phpcs:ignore
			return $page;
		}

		return false;
	}

	/**
	 * Get any extra actions that might be needed.
	 *
	 * @param array  $actions Actions.
	 * @param string $type Type.
	 * @param object $result Result.
	 * @return array
	 */
	public function extra_actions( array $actions, $type, $result ) {
		if ( $type === 'tablepress_table' ) {
			$tables = json_decode( get_option( 'tablepress_tables' ), true );

			if ( is_array( $tables ) ) {
				foreach ( $tables['table_post'] as $id => $post_id ) {
					if ( $post_id === $result->get_row_id() ) {
						$actions['edit'] = 'admin.php?page=tablepress&action=edit&table_id=' . rawurlencode( $id );
						break;
					}
				}
			}
		}

		return $actions;
	}
}

register_activation_hook( SEARCHREGEX_FILE, array( '\SearchRegex\Admin\Admin', 'plugin_activated' ) );
add_action( 'init', array( '\SearchRegex\Admin\Admin', 'init' ) );
