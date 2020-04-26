<?php

define( 'SEARCHREGEX_OPTION', 'searchregex_options' );
define( 'SEARCHREGEX_API_JSON', 0 );
define( 'SEARCHREGEX_API_JSON_INDEX', 1 );
define( 'SEARCHREGEX_API_JSON_RELATIVE', 3 );

function searchregex_get_default_options() {
	$defaults = [
		'support' => false,
		'rest_api' => SEARCHREGEX_API_JSON,
	];

	return apply_filters( 'searchregex_default_options', $defaults );
}

function searchregex_set_options( array $settings = array() ) {
	$options = searchregex_get_options();
	$monitor_types = array();

	if ( isset( $settings['rest_api'] ) && in_array( intval( $settings['rest_api'], 10 ), array( 0, 1, 2, 3, 4 ), true ) ) {
		$options['rest_api'] = intval( $settings['rest_api'], 10 );
	}

	if ( isset( $settings['support'] ) ) {
		$options['support'] = $settings['support'] ? true : false;
	}

	update_option( SEARCHREGEX_OPTION, apply_filters( 'searchregex_save_options', $options ) );
	return $options;
}

function searchregex_get_options() {
	$options = get_option( SEARCHREGEX_OPTION );
	$defaults = searchregex_get_default_options();

	foreach ( $defaults as $key => $value ) {
		if ( ! isset( $options[ $key ] ) ) {
			$options[ $key ] = $value;
		}
	}

	// Remove old options not in searchregex_get_default_options()
	foreach ( $options as $key => $value ) {
		if ( ! isset( $defaults[ $key ] ) ) {
			unset( $options[ $key ] );
		}
	}

	return $options;
}

function searchregex_get_rest_api( $type = false ) {
	if ( $type === false ) {
		$options = searchregex_get_options();
		$type = $options['rest_api'];
	}

	$url = get_rest_url();  // SEARCHREGEX_API_JSON

	if ( $type === SEARCHREGEX_API_JSON_INDEX ) {
		$url = home_url( '/index.php?rest_route=/' );
	} elseif ( $type === SEARCHREGEX_API_JSON_RELATIVE ) {
		$relative = wp_parse_url( $url, PHP_URL_PATH );

		if ( $relative ) {
			$url = $relative;
		}
	}

	return $url;
}
