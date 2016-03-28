<?php
/*
Plugin Name: Search Regex
Plugin URI: http://urbangiraffe.com/plugins/search-regex
Description: Adds search &amp; replace functionality across posts, pages, comments, and meta-data, with full regular expression support
Author: John Godley
Version: 1.4.16
Author URI: http://urbangiraffe.com/
*/

class SearchRegex {
	private static $instance = null;

	static function init() {
		if ( is_admin() ) {
			if ( is_null( self::$instance ) ) {
				self::$instance = new SearchRegex();

				load_plugin_textdomain( 'search-regex', false, dirname( plugin_basename( __FILE__ ) ).'/locale/' );
			}

			return self::$instance;
		}
	}

	function __construct() {
		add_filter( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'load-tools_page_search-regex', array( &$this, 'search_head' ) );
	}

	function search_head() {
		include dirname( __FILE__ ).'/models/search.php';
		include dirname( __FILE__ ).'/models/result.php';

		wp_enqueue_style( 'search-regex', plugin_dir_url( __FILE__ ).'admin.css' );
	}

	function admin_menu() {
		if ( current_user_can( 'administrator' ) ) {
			add_management_page( __( 'Search Regex', 'search-regex' ), __( 'Search Regex', 'search-regex' ), 'administrator', basename( __FILE__ ), array( &$this, 'admin_screen' ) );
		}
		elseif ( current_user_can( 'search_regex_read' ) ) {
			add_management_page( __( 'Search Regex', 'search-regex' ), __( 'Search Regex', 'search-regex' ), 'search_regex_read', basename( __FILE__ ), array( &$this, 'admin_screen' ) );
		}
	}

	function admin_screen() {
		$searches = Search::get_searches();

		if ( isset( $_POST['search_pattern'] ) && ! wp_verify_nonce( $_POST['search-regex-nonce'], 'search' ) ) {
			return;
		}

		$search_pattern = $replace_pattern = '';
		if ( isset( $_POST['search_pattern'] ) ) {
			$search_pattern  = stripslashes( $_POST['search_pattern'] );
		}

		if ( isset( $_POST['replace_pattern'] ) ) {
			$replace_pattern = stripslashes( $_POST['replace_pattern'] );
		}

		$search_pattern  = str_replace( "\'", "'", $search_pattern );
		$replace_pattern = str_replace( "\'", "'", $replace_pattern );
		$orderby         = 'asc';

		if ( isset( $_POST['orderby'] ) && $_POST['orderby'] === 'desc' ) {
			$orderby = 'desc';
		}

		$limit  = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
		$offset = 0;
		$source = isset( $_POST['source'] ) ? stripslashes( $_POST['source'] ) : '';

		if ( Search::valid_search( $source ) && ( isset( $_POST['search'] ) || isset( $_POST['replace'] ) || isset( $_POST['replace_and_save'] ) ) ) {
			$klass    = stripslashes( $source );
			$searcher = new $klass;

			if ( isset( $_POST['regex'] ) ) {
				$searcher->set_regex_options( isset( $_POST['regex_dot'] ) ? $_POST['regex_dot'] : false, isset( $_POST['regex_case'] ) ? $_POST['regex_case'] : false, isset( $_POST['regex_multi'] ) ? $_POST['regex_multi'] : false );
			}

			// Make sure no one sneaks in with a replace
			if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'search_regex_write' ) ) {
				unset( $_POST['replace'] );
				unset( $_POST['replace_and_save'] );
				$_POST['search'] = 'search';
			}

			$results = array();

			if ( isset( $_POST['search'] ) ) {
				$results = $searcher->search_for_pattern( $search_pattern, $limit, $offset, $orderby );
			}
			elseif ( isset( $_POST['replace'] ) ) {
				$results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby );
			}
			elseif ( isset( $_POST['replace_and_save'] ) ) {
				$results = $searcher->search_and_replace( $search_pattern, $replace_pattern, $limit, $offset, $orderby, true );
			}

			if ( ! is_array( $results ) ) {
				$this->render_error( $results );
			}
			elseif ( isset( $_POST['replace_and_save'] ) ) {
?>
				<div class="updated" id="message" onclick="this.parentNode.removeChild (this)">
				 <p><?php printf( _n( '%d occurrence replaced', '%d occurrences replaced', count( $results ) ), count( $results ) ) ?></p>
				</div>
<?php
			}

			$this->render( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );

			if ( is_array( $results ) && ! isset( $_POST['replace_and_save'] ) ) {
				$this->render( 'results', array( 'search' => $searcher, 'results' => $results ) );
			}
		}
		else {
			$this->render( 'search', array( 'search' => $search_pattern, 'replace' => $replace_pattern, 'searches' => $searches, 'source' => $source ) );
		}
	}

	private function render( $template, $template_vars = array() ) {
		foreach ( $template_vars as $key => $val ) {
			$$key = $val;
		}

		if ( file_exists( dirname( __FILE__ )."/view/$template.php" ) )
			include dirname( __FILE__ )."/view/$template.php";
	}

	function render_error( $message ) {
	?>
<div class="fade error" id="message">
	<p><?php echo $message ?></p>
</div>
<?php
	}
}

if ( is_admin() ) {
	add_action( 'init', array( 'SearchRegex', 'init' ) );
}
