<?php

use Brain\Monkey\Functions;

class OpcacheClearTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Load the plugin file to get the real function
		require_once PLUGIN_PATH . '/search-regex.php';
	}

	protected function setUp(): void {
		parent::setUp();

		// Stub functions for each test (Brain\Monkey tears down stubs after each test)
		Functions\stubs( [
			'plugin_basename' => 'search-regex/search-regex.php',
		] );
		Functions\when( 'function_exists' )->justReturn( true );
	}

	public function testDoesNotCallOpcacheResetWhenActionIsNotUpdate() {
		Functions\expect( 'opcache_reset' )->never();

		$options = [
			'action' => 'install',
			'type' => 'plugin',
			'plugins' => [ 'search-regex/search-regex.php' ],
		];

		searchregex_clear_opcache_on_upgrade( null, $options );

		$this->assertTrue( true ); // Explicit assertion to avoid risky test warning
	}

	public function testDoesNotCallOpcacheResetWhenTypeIsNotPlugin() {
		Functions\expect( 'opcache_reset' )->never();

		$options = [
			'action' => 'update',
			'type' => 'theme',
			'plugins' => [ 'search-regex/search-regex.php' ],
		];

		searchregex_clear_opcache_on_upgrade( null, $options );

		$this->assertTrue( true );
	}

	public function testDoesNotCallOpcacheResetWhenPluginNotInList() {
		Functions\expect( 'opcache_reset' )->never();

		$options = [
			'action' => 'update',
			'type' => 'plugin',
			'plugins' => [ 'other-plugin/other-plugin.php' ],
		];

		searchregex_clear_opcache_on_upgrade( null, $options );

		$this->assertTrue( true );
	}

	public function testDoesNotCallOpcacheResetWhenPluginsKeyIsMissing() {
		Functions\expect( 'opcache_reset' )->never();

		$options = [
			'action' => 'update',
			'type' => 'plugin',
		];

		searchregex_clear_opcache_on_upgrade( null, $options );

		$this->assertTrue( true );
	}

	public function testCallsOpcacheResetWhenConditionsMet() {
		Functions\expect( 'opcache_reset' )->once();

		$options = [
			'action' => 'update',
			'type' => 'plugin',
			'plugins' => [ 'search-regex/search-regex.php' ],
		];

		searchregex_clear_opcache_on_upgrade( null, $options );

		$this->assertTrue( true ); // Mockery verifies the expectation in tearDown
	}
}
