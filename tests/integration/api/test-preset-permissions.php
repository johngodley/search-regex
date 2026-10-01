<?php

use SearchRegex\Plugin;
use SearchRegex\Search;

class PresetPermissionsApiTest extends SearchRegex_Api_Test {
	private $preset_ids = [];

	public function setUp(): void {
		parent::setUp();

		delete_option( Search\Preset::OPTION_NAME );

		$this->preset_ids = [
			'posts' => $this->createPreset( 'Posts', [ 'source' => [ 'posts' ], 'searchPhrase' => 'cat' ] ),
			'options' => $this->createPreset( 'Options', [ 'source' => [ 'options' ], 'searchPhrase' => 'cat' ] ),
			'user' => $this->createPreset( 'Users', [ 'source' => [ 'posts', 'user' ], 'searchPhrase' => 'cat' ] ),
			'action' => $this->createPreset(
				'Action', [
					'source' => [ 'posts' ],
					'action' => 'action',
					'actionOption' => [ 'hook' => 'init' ],
				]
			),
		];
	}

	private function createPreset( $name, array $search ) {
		$preset = new Search\Preset( array_merge( [ 'name' => $name ], $search ) );
		$preset->create();

		return $preset->get_id();
	}

	private function setDelegatedUser() {
		$this->setEditor();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_DELEGATED );
	}

	private function getPresetNames( array $presets ) {
		$names = array_map( fn( $preset ) => $preset['name'], $presets );
		sort( $names );

		return $names;
	}

	public function testAdministratorSeesAllPresets() {
		$this->setNonce();

		$result = $this->callApi( 'preset' );

		$this->assertEquals( 200, $result->status );
		$this->assertEquals( [ 'Action', 'Options', 'Posts', 'Users' ], $this->getPresetNames( $result->data['presets'] ) );
	}

	public function testDelegatedUserOnlySeesAccessiblePresets() {
		$this->setDelegatedUser();

		$result = $this->callApi( 'preset' );
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( [ 'Posts' ], $this->getPresetNames( $result->data['presets'] ) );

		$this->assertEquals( [ 'Posts' ], $this->getPresetNames( Search\Preset::get_available() ) );
	}

	public function testDelegatedUserCannotCreateRestrictedPresets() {
		$this->setDelegatedUser();

		$requests = [
			[ 'name' => 'New', 'source' => [ 'options' ] ],
			[ 'name' => 'New', 'source' => [ 'posts', 'user-meta' ] ],
			[ 'name' => 'New', 'source' => [ 'posts' ], 'action' => 'action', 'actionOption' => [ 'hook' => 'init' ] ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( 'preset', $request, 'POST' );

			$this->assertEquals( 403, $result->status, wp_json_encode( $request ) );
		}

		$this->assertCount( 4, Search\Preset::get_all() );
	}

	public function testDelegatedUserCanCreateOrdinaryPreset() {
		$this->setDelegatedUser();

		$result = $this->callApi( 'preset', [ 'name' => 'New', 'source' => [ 'comment' ] ], 'POST' );

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertEquals( [ 'New', 'Posts' ], $this->getPresetNames( $result->data['presets'] ) );
	}

	public function testDelegatedUserCannotUpdateOrDeleteRestrictedPresets() {
		$this->setDelegatedUser();

		foreach ( [ 'options', 'user', 'action' ] as $key ) {
			$id = $this->preset_ids[ $key ];

			$update = $this->callApi( 'preset/id/' . $id, [ 'name' => 'Changed', 'source' => [ 'posts' ] ], 'POST' );
			$this->assertEquals( 404, $update->status, $key );

			$delete = $this->callApi( 'preset/id/' . $id . '/delete', [], 'POST' );
			$this->assertEquals( 404, $delete->status, $key );
		}

		$this->assertCount( 4, Search\Preset::get_all() );
	}

	public function testDelegatedUserCannotMakePresetRestricted() {
		$this->setDelegatedUser();
		$id = $this->preset_ids['posts'];

		$requests = [
			[ 'name' => 'Posts', 'source' => [ 'options' ] ],
			[ 'name' => 'Posts', 'action' => 'action', 'actionOption' => [ 'hook' => 'init' ] ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( 'preset/id/' . $id, $request, 'POST' );

			$this->assertEquals( 403, $result->status, wp_json_encode( $request ) );
		}

		$this->assertTrue( Search\Preset::get( $id )->can_access() );
	}

	public function testDelegatedUserCanUpdateAndDeleteOrdinaryPreset() {
		$this->setDelegatedUser();
		$id = $this->preset_ids['posts'];

		$update = $this->callApi( 'preset/id/' . $id, [ 'name' => 'Changed', 'source' => [ 'comment' ] ], 'POST' );
		$this->assertEquals( 200, $update->status, wp_json_encode( $update->data ) );
		$this->assertEquals( 'Changed', $update->data['current']['name'] );

		$delete = $this->callApi( 'preset/id/' . $id . '/delete', [], 'POST' );
		$this->assertEquals( 200, $delete->status, wp_json_encode( $delete->data ) );
		$this->assertCount( 3, Search\Preset::get_all() );
	}

	public function testDelegatedUserImportSkipsRestrictedPresets() {
		$this->setDelegatedUser();

		$file = wp_tempnam( 'presets.json' );
		file_put_contents(
			$file, wp_json_encode(
				[
					[ 'name' => 'Imported posts', 'search' => [ 'source' => [ 'posts' ] ] ],
					[ 'name' => 'Imported options', 'search' => [ 'source' => [ 'options' ] ] ],
					[ 'name' => 'Imported action', 'search' => [ 'source' => [ 'posts' ], 'action' => 'action', 'actionOption' => [ 'hook' => 'init' ] ] ],
				]
			)
		);

		$this->assertSame( 1, Search\Preset::import( $file ) );
		unlink( $file );

		$this->assertEquals( [ 'Imported posts', 'Posts' ], $this->getPresetNames( Search\Preset::get_available() ) );
	}
}
