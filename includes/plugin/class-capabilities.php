<?php

namespace SearchRegex\Plugin;

/**
 * Search Regex capabilities
 *
 * Capabilities are real, directly-checkable WordPress capabilities. Grant one to a role or
 * user with any standard role/permission-management plugin, or with `add_cap()`, and
 * `has_access()` picks it up immediately - no upgrade step required.
 *
 * If the current user doesn't hold a specific capability, access falls back to whoever holds
 * the plugin's base access capability (`manage_options` by default, filterable with
 * `searchregex_role`). This fallback can only be used to *extend* access to additional roles -
 * it can't be used to remove access from whoever already holds base access.
 *
 * The admin menu is shown to anyone with access to at least one page, and each page is then
 * checked against its own capability. REST search, source, and connectivity routes require
 * `search_regex_manage`; settings routes require `search_regex_options`; and preset routes require
 * `search_regex_presets`. The support page has no data route. For backwards compatibility, REST checks continue to pass the legacy
 * manage permission name to the deprecated capability filter.
 *
 * Capabilities:
 * - `search_regex_manage` - access to search & replace
 * - `search_regex_options` - access to plugin settings
 * - `search_regex_support` - access to the support page
 * - `search_regex_presets` - access to presets
 *
 * Other filters:
 * - `searchregex_capability_pages( $pages )` - filters the list of available pages
 * - `searchregex_role( $cap )` - return the role/capability used for overall access to the plugin
 *
 * Deprecated: the `searchregex_capability_check( $capability, $permission_name )` filter is
 * deprecated in favour of granting the capabilities above directly. It's fully supported for
 * now - a registered callback continues to take full precedence over the capabilities above -
 * but it will be removed in a future major version.
 *
 * ```php
 * // Old way (deprecated, still works if already in place):
 * add_filter( 'searchregex_capability_check', function( $capability, $permission_name ) {
 *     if ( $permission_name === 'searchregex_cap_options' ) {
 *         return $capability;
 *     }
 *
 *     return 'manage_options';
 * } );
 *
 * // New way - grant the real capability to a role directly, e.g. via a role/permission
 * // management plugin, or in code:
 * add_action( 'admin_init', function() {
 *     get_role( 'editor' )->add_cap( 'search_regex_options' );
 * } );
 * ```
 *
 * @phpstan-type CapabilityName 'search_regex_manage'|'search_regex_options'|'search_regex_support'|'search_regex_presets'
 * @phpstan-type LegacyCapabilityName 'searchregex_cap_manage'|'searchregex_cap_options'|'searchregex_cap_support'|'searchregex_cap_preset'
 * @phpstan-type PageName 'search'|'options'|'support'|'presets'
 * @phpstan-type CapabilityDefinition array{capability: CapabilityName, legacy: LegacyCapabilityName, page: PageName}
 */
class Capabilities {
	const FILTER_ALL = 'searchregex_capability_all';
	const FILTER_PAGES = 'searchregex_capability_pages';

	// Deprecated - see the class docblock @deprecated note
	const FILTER_CAPABILITY = 'searchregex_capability_check';

	// The default WordPress capability used for all checks
	const CAP_DEFAULT = 'manage_options';

	// The main capability used to provide access to the plugin
	const CAP_PLUGIN = 'searchregex_role';

	// Real, directly-checkable capabilities
	const CAP_SEARCHREGEX_SEARCH = 'search_regex_manage';
	const CAP_SEARCHREGEX_OPTIONS = 'search_regex_options';
	const CAP_SEARCHREGEX_SUPPORT = 'search_regex_support';
	const CAP_SEARCHREGEX_PRESETS = 'search_regex_presets';

	// Deprecated pseudo-capability names, retained only as the `$permission_name` passed to the
	// deprecated `searchregex_capability_check` filter. Don't use these for new capability checks.
	const LEGACY_CAP_SEARCHREGEX_SEARCH = 'searchregex_cap_manage';
	const LEGACY_CAP_SEARCHREGEX_OPTIONS = 'searchregex_cap_options';
	const LEGACY_CAP_SEARCHREGEX_SUPPORT = 'searchregex_cap_support';
	const LEGACY_CAP_SEARCHREGEX_PRESETS = 'searchregex_cap_preset';

	/**
	 * Determine if the current user has access to a named capability.
	 *
	 * @param CapabilityName $cap_name The real capability to check for. See Capabilities for constants.
	 * @param LegacyCapabilityName|null $legacy_name The deprecated pseudo-capability name for this check, passed
	 * to the deprecated `searchregex_capability_check` filter if a site has registered one. Defaults to the
	 * legacy name that matches `$cap_name`.
	 * @return bool
	 */
	public static function has_access( $cap_name, $legacy_name = null ) {
		// Deprecated: if a site has customized access with the old filter, it still takes full precedence
		if ( has_filter( self::FILTER_CAPABILITY ) ) {
			if ( $legacy_name === null ) {
				$legacy_name = self::get_legacy_name( $cap_name );
			}

			$cap_to_check = apply_filters( self::FILTER_CAPABILITY, self::get_plugin_access(), $legacy_name );

			return current_user_can( $cap_to_check );
		}

		// Explicitly granted, e.g. by a role/permission-management plugin
		if ( current_user_can( $cap_name ) ) {
			return true;
		}

		// Default: same base access capability as always (manage_options, filterable via `searchregex_role`)
		return current_user_can( self::get_plugin_access() );
	}

	/**
	 * Return the deprecated legacy name that matches a capability.
	 *
	 * @param string $cap_name Capability name.
	 * @return string Legacy name, or `$cap_name` if it isn't a known capability
	 */
	private static function get_legacy_name( $cap_name ) {
		foreach ( self::get_capability_definitions() as $definition ) {
			if ( $definition['capability'] === $cap_name ) {
				return $definition['legacy'];
			}
		}

		return $cap_name;
	}

	/**
	 * Return the capability used to register the plugin admin menu.
	 *
	 * The menu is shown to anyone with access to at least one plugin page. Individual pages are
	 * still checked separately. `add_management_page()` only accepts a single capability, so this
	 * returns a capability the current user holds when they have access through a specific capability.
	 *
	 * @return string Role/capability
	 */
	public static function get_menu_capability() {
		$base = self::get_plugin_access();

		if ( current_user_can( $base ) ) {
			return $base;
		}

		foreach ( self::get_capability_definitions() as $definition ) {
			if ( self::has_access( $definition['capability'], $definition['legacy'] ) && current_user_can( $definition['capability'] ) ) {
				return $definition['capability'];
			}
		}

		return $base;
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
	 * Every capability this plugin defines, paired with its deprecated legacy name and the page it gates.
	 *
	 * @return list<CapabilityDefinition>
	 */
	private static function get_capability_definitions() {
		return [
			[
				'capability' => self::CAP_SEARCHREGEX_SEARCH,
				'legacy' => self::LEGACY_CAP_SEARCHREGEX_SEARCH,
				'page' => 'search',
			],
			[
				'capability' => self::CAP_SEARCHREGEX_OPTIONS,
				'legacy' => self::LEGACY_CAP_SEARCHREGEX_OPTIONS,
				'page' => 'options',
			],
			[
				'capability' => self::CAP_SEARCHREGEX_SUPPORT,
				'legacy' => self::LEGACY_CAP_SEARCHREGEX_SUPPORT,
				'page' => 'support',
			],
			[
				'capability' => self::CAP_SEARCHREGEX_PRESETS,
				'legacy' => self::LEGACY_CAP_SEARCHREGEX_PRESETS,
				'page' => 'presets',
			],
		];
	}

	/**
	 * Return all the pages the user has access to.
	 *
	 * @return list<PageName> Array of pages
	 */
	public static function get_available_pages() {
		$available = [];

		foreach ( self::get_capability_definitions() as $definition ) {
			if ( self::has_access( $definition['capability'], $definition['legacy'] ) ) {
				$available[] = $definition['page'];
			}
		}

		return array_values( apply_filters( self::FILTER_PAGES, $available ) );
	}

	/**
	 * Return all the capabilities the current user has
	 *
	 * @return list<CapabilityName> Array of capabilities
	 */
	public static function get_all_capabilities() {
		$granted = [];

		foreach ( self::get_capability_definitions() as $definition ) {
			if ( self::has_access( $definition['capability'], $definition['legacy'] ) ) {
				$granted[] = $definition['capability'];
			}
		}

		return array_values( apply_filters( self::FILTER_ALL, $granted ) );
	}

	/**
	 * Unfiltered list of all the supported capabilities, without influence from the current user
	 *
	 * @return list<CapabilityName> Array of capabilities
	 */
	public static function get_every_capability() {
		return array_map(
			fn( $definition ) => $definition['capability'],
			self::get_capability_definitions()
		);
	}
}
