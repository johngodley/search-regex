<?php

namespace SearchRegex\Plugin;

/**
 * Search Regex capabilities
 *
 * Hook `searchregex_role` and return a capability for access to the plugin menu. For example `edit_pages` will allow an editor
 * Hook `searchregex_capability` and return a different capability for each check that needs specific permissions.
 *
 * For example, if you want to give editors access to create redirects, but nothing else:
 *
 * ```php
 * add_filter( 'searchregex_capability_check', function( $capability, $permission_name ) {
 *     if ( $permission_name === 'searchregex_cap_redirect_manage' || $permission_name === 'searchregex_cap_redirect_add' ) {
 *         return $capability;
 *     }
 *
 *     return 'manage_options';
 * } );
 * ```
 *
 * Always default to restrictive and then grant permissions. Don't default to permissive and remove permissions. This way if a new
 * capability is added your users won't automatically be granted access.
 *
 * Capabilities can be filtered with:
 * - `searchregex_capability_check( $capability, $permission_name )` - override `$capability` dependant on `$permission_name`
 * - `searchregex_capability_pages( $pages )` - filters the list of available pages
 * - `searchregex_role( $cap )` - return the role/capability used for overall access to the plugin
 *
 * Note some capabilities may give access to data from others. For example, when viewing a page of redirects via `searchregex_cap_redirect_manage`
 * the client will need to access group data.
 */
class Capabilities {
	const FILTER_ALL = 'searchregex_capability_all';
	const FILTER_PAGES = 'searchregex_capability_pages';
	const FILTER_CAPABILITY = 'searchregex_capability_check';

	// The default WordPress capability used for all checks
	const CAP_DEFAULT = 'manage_options';

	// The main capability used to provide access to the plugin
	const CAP_PLUGIN = 'searchregex_role';

	// These capabilities are combined with `searchregex_cap_` to form `searchregex_cap_redirect_add` etc
	const CAP_SEARCHREGEX_SEARCH = 'searchregex_cap_manage';
	const CAP_SEARCHREGEX_OPTIONS = 'searchregex_cap_options';
	const CAP_SEARCHREGEX_SUPPORT = 'searchregex_cap_support';
	const CAP_SEARCHREGEX_PRESETS = 'searchregex_cap_preset';

	/**
	 * Determine if the current user has access to a named capability.
	 *
	 * @param string $cap_name The capability to check for. See Capabilities for constants.
	 * @return boolean
	 */
	public static function has_access( $cap_name ) {
		// Get the capability using the default plugin access as the base. Old sites overriding `searchregex_role` will get access to everything
		/** @psalm-suppress TooManyArguments */
		$cap_to_check = apply_filters( self::FILTER_CAPABILITY, self::get_plugin_access(), $cap_name );

		// Check the capability
		return current_user_can( $cap_to_check );
	}

	/**
	 * Return the role/capability used for displaying the plugin menu. This is also the base capability for all other checks.
	 *
	 * @return string Role/capability
	 */
	public static function get_plugin_access() {
		return apply_filters( self::CAP_PLUGIN, self::CAP_DEFAULT );
	}

	/**
	 * Return all the pages the user has access to.
	 *
	 * @return array Array of pages
	 */
	public static function get_available_pages() {
		$pages = [
			self::CAP_SEARCHREGEX_SEARCH => 'search',
			self::CAP_SEARCHREGEX_OPTIONS => 'options',
			self::CAP_SEARCHREGEX_SUPPORT => 'support',
			self::CAP_SEARCHREGEX_PRESETS => 'presets',
		];

		$available = [];
		foreach ( $pages as $key => $page ) {
			if ( self::has_access( $key ) ) {
				$available[] = $page;
			}
		}

		return array_values( apply_filters( self::FILTER_PAGES, $available ) );
	}

	/**
	 * Return all the capabilities the current user has
	 *
	 * @return array Array of capabilities
	 */
	public static function get_all_capabilities() {
		$caps = self::get_every_capability();

		$caps = array_filter( $caps, function( $cap ) {
			return self::has_access( $cap );
		} );

		return array_values( apply_filters( self::FILTER_ALL, $caps ) );
	}

	/**
	 * Unfiltered list of all the supported capabilities, without influence from the current user
	 *
	 * @return array Array of capabilities
	 */
	public static function get_every_capability() {
		return [
			self::CAP_SEARCHREGEX_SEARCH,
			self::CAP_SEARCHREGEX_OPTIONS,
			self::CAP_SEARCHREGEX_SUPPORT,
			self::CAP_SEARCHREGEX_PRESETS,
		];
	}
}
