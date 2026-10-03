<?php

use SearchRegex\Admin;
use SearchRegex\Plugin;
use SearchRegex\Search;

class SearchRegexAdminPayloadTest extends SearchRegex_Api_Test {
	public function setUp(): void {
		parent::setUp();

		delete_option( Search\Preset::OPTION_NAME );
		delete_option( Plugin\Settings::OPTION_NAME );

		// Settings are a singleton, so reset it to pick up the deleted option
		$instance = new ReflectionProperty( Plugin\Plugin_Settings::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		( new Search\Preset( [ 'name' => 'Posts', 'source' => [ 'posts' ] ] ) )->create();
		( new Search\Preset( [ 'name' => 'Options', 'source' => [ 'options' ] ] ) )->create();

		set_current_screen( 'tools_page_search-regex' );
	}

	public function tearDown(): void {
		unset( $_REQUEST['action'], $_REQUEST['_wpnonce'], $_REQUEST['rest_api'] );
		wp_deregister_script( 'search-regex' );

		parent::tearDown();
	}

	private function setDelegatedUser() {
		$this->setEditor();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_DELEGATED );
	}

	private function getPayload() {
		( new Admin\Admin() )->searchregex_head();

		$data = wp_scripts()->get_data( 'search-regex', 'data' );
		$this->assertIsString( $data );
		$this->assertSame( 1, preg_match( '/var SearchRegexi10n = (.*);/s', $data, $matches ) );

		return json_decode( $matches[1], true );
	}

	private function getNames( array $items ) {
		return array_map( fn( $item ) => $item['name'], $items );
	}

	private function getSourceNames( array $groups ) {
		$names = [];

		foreach ( $groups as $group ) {
			$names = array_merge( $names, $this->getNames( $group['sources'] ) );
		}

		return $names;
	}

	public function testAdministratorPayload() {
		$this->setNonce();

		$payload = $this->getPayload();

		$this->assertTrue( $payload['caps']['admin'] );
		$this->assertArrayHasKey( 'update_notice', $payload['settings'] );
		$this->assertEqualsCanonicalizing( [ 'Posts', 'Options' ], $this->getNames( $payload['preload']['presets'] ) );
		$this->assertContains( 'options', $this->getSourceNames( $payload['preload']['sources'] ) );
	}

	public function testDelegatedPayload() {
		$this->setDelegatedUser();

		$payload = $this->getPayload();

		$this->assertFalse( $payload['caps']['admin'] );
		$this->assertNotContains( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, $payload['caps']['capabilities'] );
		$this->assertEqualsCanonicalizing(
			[ 'support', 'rest_api', 'startupMode', 'startupPreset', 'defaultPreset' ],
			array_keys( $payload['settings'] )
		);
		$this->assertEquals( [ 'Posts' ], $this->getNames( $payload['preload']['presets'] ) );

		$sources = $this->getSourceNames( $payload['preload']['sources'] );
		$schema = array_map( fn( $item ) => $item['type'], $payload['preload']['schema'] );

		foreach ( [ 'user', 'user-meta', 'options' ] as $sensitive ) {
			$this->assertNotContains( $sensitive, $sources );
			$this->assertNotContains( $sensitive, $schema );
		}

		$this->assertContains( 'posts', $sources );
	}

	private function requestRestApiChange() {
		$_REQUEST['action'] = 'rest_api';
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'wp_rest' );
		$_REQUEST['rest_api'] = (string) Plugin\Settings::API_JSON_RELATIVE;

		$this->getPayload();

		return Plugin\Settings::init()->get_rest_api();
	}

	public function testDelegatedUserCannotChangeRestApi() {
		$this->setDelegatedUser();

		$this->assertSame( Plugin\Settings::API_JSON, $this->requestRestApiChange() );
	}

	public function testAdministratorCanChangeRestApi() {
		$this->setNonce();

		$this->assertSame( Plugin\Settings::API_JSON_RELATIVE, $this->requestRestApiChange() );
	}
}
