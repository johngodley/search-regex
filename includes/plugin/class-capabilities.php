<?php

namespace SearchRegex\Plugin;

/**
 * Search Regex capabilities
 *
 * There are two levels of access:
 *
 * - Administrators - anyone with the plugin's base access capability (`manage_options` by default,
 *   filterable with `searchregex_role`). They have access to everything.
 * - Delegated users - anyone granted the `search_regex_manage` capability. They can search & replace,
 *   manage presets, and view the support page. Plugin settings, user data, WordPress options, and
 *   running action hooks remain administrator-only. The UI hides the action hook option, and presets
 *   that use it, from delegated users entirely, although the API allows a dry run.
 *
 * Delegated access is a privilege that an administrator grants to trusted users. Grant it to a role or
 * user with any standard role/permission-management plugin, or with `add_cap()`:
 *
 * ```php
 * add_action( 'admin_init', function() {
 *     get_role( 'editor' )->add_cap( 'search_regex_manage' );
 * } );
 * ```
 *
 * Permissions:
 * - `searchregex_cap_manage` - access to search & replace
 * - `searchregex_cap_options` - access to plugin settings
 * - `searchregex_cap_support` - access to the support page
 * - `searchregex_cap_preset` - access to presets
 *
 * Other filters:
 * - `searchregex_capability_pages( $pages )` - filters the list of available pages
 * - `searchregex_role( $cap )` - return the role/capability used for overall access to the plugin
 *
 * Deprecated: the `searchregex_capability_check( $capability, $permission_name )` filter is
 * deprecated in favour of granting `search_regex_manage`. It's fully supported for now - a registered
 * callback takes full precedence over the access levels above - but it will be removed in a future
 * major version.
 *
 * @phpstan-type PermissionName 'searchregex_cap_manage'|'searchregex_cap_options'|'searchregex_cap_support'|'searchregex_cap_preset'
 * @phpstan-type PageName 'search'|'options'|'support'|'presets'
 */
class Capabilities {
	const FILTER_ALL = 'searchregex_capability_all';
	const FILTER_PAGES = 'searchregex_capability_pages';

	// Deprecated - see the class docblock
	const FILTER_CAPABILITY = 'searchregex_capability_check';

	// The default WordPress capability used for all checks
	const CAP_DEFAULT = 'manage_options';

	// The main capability used to provide access to the plugin
	const CAP_PLUGIN = 'searchregex_role';

	// Real WordPress capability that an administrator can grant to delegate access
	const CAP_DELEGATED = 'search_regex_manage';

	// Permissions checked with `has_access()`
	const CAP_SEARCHREGEX_SEARCH = 'searchregex_cap_manage';
	const CAP_SEARCHREGEX_OPTIONS = 'searchregex_cap_options';
	const CAP_SEARCHREGEX_SUPPORT = 'searchregex_cap_support';
	const CAP_SEARCHREGEX_PRESETS = 'searchregex_cap_preset';

	/**
	 * Determine if the current user has a permission.
	 *
	 * @param PermissionName $cap_name The permission to check for. See Capabilities for constants.
	 * @return bool
	 */
	public static function has_access( $cap_name ) {
		// Deprecated: if a site has customized access with the old filter, it still takes full precedence
		if ( self::has_legacy_filter() ) {
			$cap_to_check = apply_filters( self::FILTER_CAPABILITY, self::get_plugin_access(), $cap_name );

			return current_user_can( $cap_to_check );
		}

		if ( current_user_can( self::get_plugin_access() ) ) {
			return true;
		}

		return $cap_name !== self::CAP_SEARCHREGEX_OPTIONS && current_user_can( self::CAP_DELEGATED );
	}

	/**
	 * Determine if the current user is a Search Regex administrator, with access to administrator-only
	 * features such as sensitive sources. Delegated users are not administrators.
	 *
	 * @return bool
	 */
	public static function is_administrator() {
		// Deprecated: the old filter has always granted everything with the manage permission
		if ( self::has_legacy_filter() ) {
			return self::has_access( self::CAP_SEARCHREGEX_SEARCH );
		}

		return current_user_can( self::get_plugin_access() );
	}

	/**
	 * Determine if the deprecated capability filter is in use. `has_filter()` returns the callback priority, which can be 0.
	 *
	 * @return bool
	 */
	private static function has_legacy_filter() {
		return has_filter( self::FILTER_CAPABILITY ) !== false;
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
	 * Return the capability used to register the plugin admin menu. This is the base capability, or the
	 * delegated capability for a delegated user. `add_management_page()` only accepts a single capability.
	 *
	 * @return string Role/capability
	 */
	public static function get_menu_capability() {
		$base = self::get_plugin_access();

		if ( ! self::has_legacy_filter() && ! current_user_can( $base ) && current_user_can( self::CAP_DELEGATED ) ) {
			return self::CAP_DELEGATED;
		}

		return $base;
	}

	/**
	 * Return all the pages the user has access to.
	 *
	 * @return list<PageName> Array of pages
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
	 * Return all the permissions the current user has
	 *
	 * @return list<PermissionName> Array of permissions
	 */
	public static function get_all_capabilities() {
		$caps = array_filter(
			self::get_every_capability(), fn( $cap ) => self::has_access( $cap )
		);

		return array_values( apply_filters( self::FILTER_ALL, $caps ) );
	}

	/**
	 * Unfiltered list of all the supported permissions, without influence from the current user
	 *
	 * @return list<PermissionName> Array of permissions
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
