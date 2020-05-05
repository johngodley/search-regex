<?php

require_once ABSPATH . 'wp-admin/includes/plugin.php';

class RedirectionApiPluginTest extends Redirection_Api_Test {
	private function get_endpoints() {
		return [
			[ 'plugin/test', 'GET', [] ],
			[ 'plugin/test', 'POST', [] ],
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
}
