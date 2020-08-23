<?php

/** @var String */
define( 'SEARCHREGEX_OPTION', 'searchregex_options' );
/** @var String */
define( 'SEARCHREGEX_API_JSON', 0 );
/** @var String */
define( 'SEARCHREGEX_API_JSON_INDEX', 1 );
/** @var String */
define( 'SEARCHREGEX_API_JSON_RELATIVE', 3 );

/**
 * Get default Search Regex options
 *
 * @return Array
 */
function searchregex_get_default_options() {
	$defaults = [
		'support' => false,
		'rest_api' => SEARCHREGEX_API_JSON,
		'actionDropdown' => true,
		'defaultPreset' => 0,
	];

	return \apply_filters( 'searchregex_default_options', $defaults );
}

/**
 * Set Search Regex options. Can be passed as many options as necessary and the rest will be unchanged
 *
 * @param Array $settings Array of name => value.
 * @return Array Array of name => value
 */
function searchregex_set_options( array $settings = array() ) {
	$options = searchregex_get_options();

	if ( isset( $settings['rest_api'] ) && in_array( intval( $settings['rest_api'], 10 ), array( 0, 1, 2, 3, 4 ), true ) ) {
		$options['rest_api'] = intval( $settings['rest_api'], 10 );
	}

	if ( isset( $settings['support'] ) ) {
		$options['support'] = $settings['support'] ? true : false;
	}

	if ( isset( $settings['actionDropdown'] ) ) {
		$options['actionDropdown'] = $settings['actionDropdown'] ? true : false;
	}

	if ( isset( $settings['defaultPreset'] ) ) {
		$options['defaultPreset'] = preg_replace( '/[^A-Fa-f0-9]*/', '', $settings['defaultPreset'] );
	}

	\update_option( SEARCHREGEX_OPTION, \apply_filters( 'searchregex_save_options', $options ) );
	return $options;
}

/**
 * Return Search Regex options
 *
 * @return Array Array of data
 */
function searchregex_get_options() {
	$options = \get_option( SEARCHREGEX_OPTION );
	$defaults = searchregex_get_default_options();

	foreach ( $defaults as $key => $value ) {
		if ( ! isset( $options[ $key ] ) ) {
			$options[ $key ] = $value;
		}
	}

	// Remove old options not in searchregex_get_default_options()
	foreach ( array_keys( $options ) as $key ) {
		if ( ! isset( $defaults[ $key ] ) ) {
			unset( $options[ $key ] );
		}
	}

	return $options;
}

/**
 * Get the configured REST API
 *
 * @param boolean $type Type of API.
 * @return String API URL
 */
function searchregex_get_rest_api( $type = false ) {
	if ( $type === false ) {
		$options = searchregex_get_options();
		$type = $options['rest_api'];
	}

	$url = \get_rest_url();  // SEARCHREGEX_API_JSON

	if ( $type === SEARCHREGEX_API_JSON_INDEX ) {
		$url = \home_url( '/index.php?rest_route=/' );
	} elseif ( $type === SEARCHREGEX_API_JSON_RELATIVE ) {
		$relative = \wp_parse_url( $url, PHP_URL_PATH );

		if ( $relative ) {
			$url = $relative;
		}
	}

	return $url;
}
