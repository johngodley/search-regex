<?php

class RedirectionApiSettingsTest extends Redirection_Api_Test {
	private function get_endpoints() {
		return [
			[ 'setting', 'GET', [] ],
			[ 'setting', 'POST', [] ],
		];
	}

	public function testNoPermission() {
		$this->setUnauthorised();

		// None of these should work
		$this->check_endpoints( $this->get_endpoints() );
	}

	public function testAdminPermission() {
		// All of these should work
		$this->check_endpoints( $this->get_endpoints(), $this->get_endpoints() );
	}

	public function testLoadSettings() {
		$this->setNonce();
		$result = $this->callApi( 'setting' );

		$this->assertTrue( is_array( $result->data['settings'] ) );
	}

	public function testSaveEmptySettingsChangesNothing() {
		$this->setNonce();

		$before = $this->callApi( 'setting' );
		update_option( 'searchregex_options', (array) $before->data['settings'] );
		$before = $before->data['settings'];

		$after = $this->callApi( 'setting', array(), 'POST' );
		$after = $after->data['settings'];

		$this->assertEquals( $before, $after );
	}

	public function testSaveSupport() {
		$data = true;
		$this->setNonce();

		$result = $this->callApi( 'setting', array( 'support' => $data ), 'POST' );
		$this->assertEquals( true, $result->data['settings']['support'] );
	}

	public function testSaveAPI() {
		$data = 1;
		$this->setNonce();

		$result = $this->callApi( 'setting', array( 'rest_api' => $data ), 'POST' );
		$this->assertEquals( 1, $result->data['settings']['rest_api'] );
	}

	public function testSaveHelp() {
		$data = true;
		$this->setNonce();

		$result = $this->callApi( 'setting', array( 'help' => $data ), 'POST' );
		$this->assertEquals( true, $result->data['settings']['help'] );

		$data = false;
		$result = $this->callApi( 'setting', array( 'help' => $data ), 'POST' );
		$this->assertEquals( false, $result->data['settings']['help'] );
	}
}
