<?php

use SearchRegex\Plugin;
use SearchRegex\Source;

class SensitiveSourcePermissionsApiTest extends SearchRegex_Api_Test {
	private function setDelegatedUser() {
		$this->setEditor();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH );
	}

	public function testDelegatedUserCanStillSearchOrdinarySources() {
		$this->setDelegatedUser();

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
			],
			'POST'
		);

		$this->assertNotEquals( 403, $result->status, wp_json_encode( $result->data ) );
	}

	public function testDelegatedUserCannotAccessUserSources() {
		$this->setDelegatedUser();

		$requests = [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts', 'user-meta' ] ], 'POST' ],
			[ 'source/user/complete/user_email', [ 'value' => 'example' ], 'GET' ],
			[ 'source/user/row/1', [], 'GET' ],
			[ 'source/user/row/1', [ 'replacement' => [ 'column' => 'display_name' ] ], 'POST' ],
			[ 'source/user/row/1/delete', [], 'POST' ],
			[ 'source/user-meta/row/1', [], 'GET' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( 403, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testDelegatedUserCannotAccessOptions() {
		$this->setDelegatedUser();

		$requests = [
			[ 'search', [ 'source' => [ 'options' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'options' ], 'action' => 'replace', 'save' => false ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts', 'options' ] ], 'POST' ],
			[ 'source/options/complete/option_name', [ 'value' => 'example' ], 'GET' ],
			[ 'source/options/row/1', [], 'GET' ],
			[ 'source/options/row/1', [ 'replacement' => [ 'column' => 'option_value' ] ], 'POST' ],
			[ 'source/options/row/1/delete', [], 'POST' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( 403, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testDelegatedUserCannotRunActionHooks() {
		$this->setDelegatedUser();

		foreach ( [ true, 'true', '1' ] as $save ) {
			$result = $this->callApi(
				'search',
				[
					'source' => [ 'posts' ],
					'action' => 'action',
					'actionOption' => [ 'hook' => 'init' ],
					'save' => $save,
				],
				'POST'
			);

			$this->assertEquals( 403, $result->status, wp_json_encode( $result->data ) );
		}

		foreach ( [ false, 'false', '0' ] as $save ) {
			$preview = $this->callApi(
				'search',
				[
					'source' => [ 'posts' ],
					'action' => 'action',
					'actionOption' => [ 'hook' => 'init' ],
					'save' => $save,
				],
				'POST'
			);

			$this->assertNotEquals( 403, $preview->status, wp_json_encode( $preview->data ) );
		}
	}

	public function testStringFalseSaveDoesNotRunActionHook() {
		$this->setNonce();
		$this->factory->post->create( [ 'post_title' => 'hook target' ] );

		$calls = 0;
		$counter = function () use ( &$calls ) {
			$calls++;
		};
		add_action( 'searchregex_test_hook', $counter );

		$this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
				'action' => 'action',
				'actionOption' => [ 'hook' => 'searchregex_test_hook' ],
				'save' => 'false',
			],
			'POST'
		);

		remove_action( 'searchregex_test_hook', $counter );
		$this->assertSame( 0, $calls );
	}

	public function testAdministratorCanAccessSensitiveSources() {
		$this->setNonce();

		$requests = [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'user-meta' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'options' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts' ], 'action' => 'action', 'actionOption' => [ 'hook' => 'init' ], 'save' => false ], 'POST' ],
			[ 'source/options/row/999999999', [ 'replacement' => [ 'column' => 'option_value' ] ], 'POST' ],
			[ 'source/options/row/999999999/delete', [], 'POST' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertNotEquals( 403, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testSensitiveSourceAliasesCannotBypassAdminAccess() {
		add_filter( 'searchregex_sources_core', [ $this, 'addUserAlias' ] );
		$this->setDelegatedUser();

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'people-alias' ],
			],
			'POST'
		);

		$this->assertEquals( 403, $result->status, wp_json_encode( $result->data ) );
		remove_filter( 'searchregex_sources_core', [ $this, 'addUserAlias' ] );
	}

	public function addUserAlias( $sources ) {
		$sources[] = [
			'name' => 'people-alias',
			'class' => Source\Core\User::class,
			'label' => 'People Alias',
			'type' => 'core',
		];

		return $sources;
	}
}
