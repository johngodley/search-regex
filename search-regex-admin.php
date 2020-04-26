<?php

require_once __DIR__ . '/models/search.php';
require_once __DIR__ . '/models/replace.php';
require_once __DIR__ . '/models/result.php';

use SearchRegex\Source_Manager;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Flags;

class Search_Regex_Admin {
	private static $instance = null;

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Search_Regex_Admin();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'plugin_action_links_' . basename( dirname( SEARCHREGEX_FILE ) ) . '/' . basename( SEARCHREGEX_FILE ), [ $this, 'plugin_settings' ], 10, 4 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 4 );
		add_filter( 'set-screen-option', [ $this, 'set_per_page' ], 10, 3 );

		add_filter( 'searchregex_sources_posttype', [ $this, 'searchregex_sources_posttype' ] );

		register_deactivation_hook( SEARCHREGEX_FILE, [ 'Search_Regex_Admin', 'plugin_deactivated' ] );
		register_uninstall_hook( SEARCHREGEX_FILE, [ 'Search_Regex_Admin', 'plugin_uninstall' ] );
	}

	// These are only called on the single standard site, or in the network admin of the multisite - they run across all available sites
	public static function plugin_activated() {
	}

	// These are only called on the single standard site, or in the network admin of the multisite - they run across all available sites
	public static function plugin_deactivated() {
	}

	// These are only called on the single standard site, or in the network admin of the multisite - they run across all available sites
	public static function plugin_uninstall() {
	}

	public function plugin_settings( $links ) {
		array_unshift( $links, '<a href="' . esc_url( $this->get_plugin_url() ) . '&amp;sub=options">' . __( 'Settings', 'search-regex' ) . '</a>' );
		return $links;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( $plugin_file === basename( dirname( SEARCHREGEX_FILE ) ) . '/' . basename( SEARCHREGEX_FILE ) ) {
			$plugin_data['Description'] .= '<p>' . __( 'Please upgrade your database', 'search-regex' ) . '</p>';
		}

		return $plugin_meta;
	}

	private function get_plugin_url() {
		return admin_url( 'tools.php?page=' . basename( SEARCHREGEX_FILE ) );
	}

	public function searchregex_sources_posttype( $sources ) {
		$blacklist = [ 'oembed_cache', 'customise_changeset', 'custom_css', 'customize_changeset', 'user_request' ];

		return array_filter( $sources, function( $source ) use ( $blacklist ) {
			return ! in_array( $source['name'], $blacklist, true );
		} );
	}

	private function get_first_available_page_url() {
		$pages = Search_Regex_Capabilities::get_available_pages();

		if ( count( $pages ) > 0 ) {
			return $this->get_plugin_url() . ( $pages[0] === 'search' ? '' : '&sub=' . rawurlencode( $pages[0] ) );
		}

		return admin_url();
	}

	public function searchregex_head() {
		global $wp_version;

		// Does user have access to this page?
		if ( $this->get_current_page() === false ) {
			// Redirect to root plugin page
			wp_safe_redirect( $this->get_first_available_page_url() );
			die();
		}

		if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) ) {
			if ( $_REQUEST['action'] === 'fixit' ) {
				$this->run_fixit();
			} elseif ( $_REQUEST['action'] === 'rest_api' ) {
				$this->set_rest_api( intval( $_REQUEST['rest_api'], 10 ) );
			}
		}

		$build = SEARCHREGEX_VERSION . '-' . SEARCHREGEX_BUILD;
		$preload = $this->get_preload_data();
		$options = searchregex_get_options();
		$versions = array(
			'Plugin: ' . SEARCHREGEX_VERSION,
			'WordPress: ' . $wp_version . ' (' . ( is_multisite() ? 'multi' : 'single' ) . ')',
			'PHP: ' . phpversion(),
			'Browser: ' . $this->get_user_agent(),
			'JavaScript: ' . plugin_dir_url( SEARCHREGEX_FILE ) . 'search-regex.js',
			'REST API: ' . searchregex_get_rest_api(),
		);

		if ( defined( 'SEARCHREGEX_DEV_MODE' ) && SEARCHREGEX_DEV_MODE ) {
			wp_enqueue_script( 'search-regex', 'http://localhost:3312/search-regex.js', array(), $build, true );
		} else {
			wp_enqueue_script( 'search-regex', plugin_dir_url( SEARCHREGEX_FILE ) . 'search-regex.js', array(), $build, true );
		}

		wp_enqueue_style( 'search-regex', plugin_dir_url( SEARCHREGEX_FILE ) . 'search-regex.css', array(), $build );

		wp_localize_script( 'search-regex', 'SearchRegexi10n', array(
			'api' => [
				'WP_API_root' => esc_url_raw( searchregex_get_rest_api() ),
				'WP_API_nonce' => wp_create_nonce( 'wp_rest' ),
				'current' => $options['rest_api'],
				'routes' => [
					SEARCHREGEX_API_JSON => searchregex_get_rest_api( SEARCHREGEX_API_JSON ),
					SEARCHREGEX_API_JSON_INDEX => searchregex_get_rest_api( SEARCHREGEX_API_JSON_INDEX ),
					SEARCHREGEX_API_JSON_RELATIVE => searchregex_get_rest_api( SEARCHREGEX_API_JSON_RELATIVE ),
				],
			],
			'pluginBaseUrl' => plugins_url( '', SEARCHREGEX_FILE ),
			'pluginRoot' => $this->get_plugin_url(),
			'locale' => $this->get_i18n_data(),
			'localeSlug' => get_locale(),
			'settings' => $options,
			'preload' => $preload,
			'versions' => implode( "\n", $versions ),
			'version' => SEARCHREGEX_VERSION,
			'caps' => [
				'pages' => Search_Regex_Capabilities::get_available_pages(),
				'capabilities' => Search_Regex_Capabilities::get_all_capabilities(),
			],
		) );

		$this->add_help_tab();
	}

	public function get_user_agent() {
		$agent = '';

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$agent = $_SERVER['HTTP_USER_AGENT'];
		}

		return $agent;
	}

	private function set_rest_api( $api ) {
		if ( $api >= 0 && $api <= SEARCHREGEX_API_JSON_RELATIVE ) {
			searchregex_set_options( array( 'rest_api' => intval( $api, 10 ) ) );
		}
	}

	private function get_preload_data() {
		$sources = Source_Manager::get_all_grouped();
		$all = Source_Manager::get_all_source_names();
		$handler = Source_Manager::get( $all, new Search_Flags(), new Source_Flags() );
		$flags = [];

		foreach ( $handler as $pos => $source ) {
			$flags[ $all[ $pos ] ] = $source->get_supported_flags();
		}

		return [
			'sources' => $sources,
			'source_flags' => $flags,
		];
	}

	private function linkify( $text, $url, $link = 'link' ) {
		return preg_replace( '@{{' . $link . '}}(.*?){{/' . $link . '}}@', '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">$1</a>', $text );
	}

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
		$content[] = '<li>' . $this->linkify( __( '{{link}}Search Flags{{/link}} - additional qualifiers for your search, to enable case insensitivity, and to enable regular expression support.', 'search-regex' ), 'https://searchregex.com/support/searching/' ). '</li>';
		$content[] = '<li>' . $this->linkify( __( '{{link}}Regular expression{{/link}} - a way of defining a pattern for text matching. Provides more advanced matches.', 'search-regex' ), 'https://searchregex.com/support/regular-expression/' ) . '</li>';
		$content[] = '<li>' . $this->linkify( __( '{{link}}Source{{/link}} - the source of data you wish to search. For example, posts, pages, or comments.', 'search-regex' ), 'https://searchregex.com/support/search-source/' ) . '</li>';
		$content[] = '<li>' . $flags . '</li>';
		$content[] = '</ul>';

		$title = __( 'Search Regex Support', 'search-regex' );

		$current_screen = get_current_screen();
		$current_screen->add_help_tab( array(
			'id'        => 'search-regex',
			'title'     => 'Search Regex',
			'content'   => "<h2>$title</h2>" . implode( "\n", $content ),
		) );
	}

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
				return json_decode( $locale_data );
			}
		}

		// Return empty if we have nothing to return so it doesn't fail when parsed in JS
		return array();
	}

	public function admin_menu() {
		$hook = add_management_page( 'Search Regex', 'Search Regex', Search_Regex_Capabilities::get_plugin_access(), basename( SEARCHREGEX_FILE ), [ $this, 'admin_screen' ] );
		add_action( 'load-' . $hook, [ $this, 'searchregex_head' ] );
	}

	private function check_minimum_wp() {
		$wp_version = get_bloginfo( 'version' );

		if ( version_compare( $wp_version, SEARCHREGEX_MIN_WP, '<' ) ) {
			return false;
		}

		return true;
	}

	public function admin_screen() {
		if ( count( Search_Regex_Capabilities::get_all_capabilities() ) === 0 ) {
			die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( $this->check_minimum_wp() === false ) {
			return $this->show_minimum_wordpress();
		}

		$this->show_main();
	}

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

	private function show_main() {
		?>
	<div id="react-modal"></div>
	<div id="react-portal"></div>
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
	 * @param string $page Current page.
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
		if ( in_array( $page, Search_Regex_Capabilities::get_available_pages(), true ) ) {
			// phpcs:ignore
			return $page;
		}

		return false;
	}
}

register_activation_hook( SEARCHREGEX_FILE, array( 'Search_Regex_Admin', 'plugin_activated' ) );

add_action( 'init', array( 'Search_Regex_Admin', 'init' ) );
