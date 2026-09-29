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

	public function testDelegatedUserCanReadButCannotChangeOptions() {
		$this->setDelegatedUser();

		$read = $this->callApi(
			'search',
			[
				'source' => [ 'options' ],
			],
			'POST'
		);
		$this->assertNotEquals( 403, $read->status, wp_json_encode( $read->data ) );

		$preview = $this->callApi(
			'search',
			[
				'source' => [ 'options' ],
				'action' => 'replace',
				'save' => false,
			],
			'POST'
		);
		$this->assertNotEquals( 403, $preview->status, wp_json_encode( $preview->data ) );

		foreach ( [ 'modify', 'replace', 'delete', 'action' ] as $action ) {
			$result = $this->callApi(
				'search',
				[
					'source' => [ 'options' ],
					'action' => $action,
					'save' => true,
				],
				'POST'
			);

			$this->assertEquals( 403, $result->status, $action . ': ' . wp_json_encode( $result->data ) );
		}

		$string_false = $this->callApi(
			'search',
			[
				'source' => [ 'options' ],
				'action' => 'replace',
				'save' => 'false',
			],
			'POST'
		);
		$this->assertEquals( 403, $string_false->status, wp_json_encode( $string_false->data ) );

		$row_save = $this->callApi(
			'source/options/row/1',
			[ 'replacement' => [ 'column' => 'option_value' ] ],
			'POST'
		);
		$this->assertEquals( 403, $row_save->status, wp_json_encode( $row_save->data ) );

		$row_delete = $this->callApi( 'source/options/row/1/delete', [], 'POST' );
		$this->assertEquals( 403, $row_delete->status, wp_json_encode( $row_delete->data ) );
	}

	public function testAdministratorCanAccessSensitiveSources() {
		$this->setNonce();

		$requests = [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'user-meta' ] ], 'POST' ],
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
