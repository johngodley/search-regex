<?php

use SearchRegex\Plugin;
use SearchRegex\Source;

class SearchRegexAdminCapabilitiesTest extends SearchRegex_Api_Test {
	private function setDelegatedUser() {
		$this->setEditor();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_DELEGATED );
	}

	public function testAdministratorMenuUsesBaseAccess() {
		$this->setNonce();

		$this->assertEquals( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );
		$this->assertEquals( [ 'search', 'options', 'support', 'presets' ], Plugin\Capabilities::get_available_pages() );
	}

	public function testUserWithoutAccessCannotSeeMenu() {
		$this->setEditor();

		$this->assertFalse( current_user_can( Plugin\Capabilities::get_menu_capability() ) );
		$this->assertEquals( [], Plugin\Capabilities::get_available_pages() );
	}

	public function testDelegatedUserCanSeeMenuButNotSettings() {
		$this->setDelegatedUser();

		$this->assertEquals( Plugin\Capabilities::CAP_DELEGATED, Plugin\Capabilities::get_menu_capability() );
		$this->assertTrue( current_user_can( Plugin\Capabilities::get_menu_capability() ) );
		$this->assertEquals( [ 'search', 'support', 'presets' ], Plugin\Capabilities::get_available_pages() );
	}

	public function testDelegatedUserCannotSeeMenuWhenLegacyFilterIsUsed() {
		$this->setDelegatedUser();
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'keepDefaultCapability' ], 10, 2 );

		$this->assertFalse( current_user_can( Plugin\Capabilities::get_menu_capability() ) );

		remove_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'keepDefaultCapability' ], 10 );
	}

	public function keepDefaultCapability( $capability, $permission_name ) {
		return $capability;
	}

	private function getSourceNames( $result ) {
		return array_map( fn( $source ) => $source['name'], $result->data );
	}

	private function getGroupedSourceNames( $groups ) {
		$names = [];

		foreach ( $groups as $group ) {
			$names = array_merge( $names, array_map( fn( $source ) => $source['name'], $group['sources'] ) );
		}

		return $names;
	}

	public function testDelegatedUserSourceListExcludesSensitiveSources() {
		$this->setDelegatedUser();

		$result = $this->callApi( 'source' );
		$names = $this->getSourceNames( $result );

		$this->assertEquals( 200, $result->status );
		$this->assertSame( array_values( $names ), $names );
		$this->assertContains( 'posts', $names );
		$this->assertNotContains( 'user', $names );
		$this->assertNotContains( 'user-meta', $names );
		$this->assertNotContains( 'options', $names );
	}

	public function testAdministratorSourceListIncludesSensitiveSources() {
		$this->setNonce();

		$names = $this->getSourceNames( $this->callApi( 'source' ) );

		$this->assertContains( 'user', $names );
		$this->assertContains( 'options', $names );
	}

	public function testGroupedSourcesAndSchemaCanExcludeSensitiveSources() {
		$names = $this->getGroupedSourceNames( Source\Manager::get_all_grouped( false ) );
		$types = array_map( fn( $schema ) => $schema['type'], Source\Manager::get_schema( [], false ) );

		$this->assertContains( 'posts', $names );
		$this->assertNotContains( 'user', $names );
		$this->assertNotContains( 'options', $names );
		$this->assertContains( 'posts', $types );
		$this->assertNotContains( 'user', $types );
		$this->assertNotContains( 'options', $types );

		$this->assertContains( 'options', $this->getGroupedSourceNames( Source\Manager::get_all_grouped() ) );
		$this->assertContains( 'options', array_map( fn( $schema ) => $schema['type'], Source\Manager::get_schema() ) );
	}
}
