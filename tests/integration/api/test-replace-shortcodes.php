<?php

/**
 * Shortcodes are dynamic data for the replacement. They must be processed in the replacement, and
 * never in the rest of the content, which may contain shortcodes of the same name.
 */
class ReplaceShortcodesApiTest extends SearchRegex_Api_Test {
	const CONTENT = 'a zebra [upper]keep[/upper] [date format="Y"] and a zebra';

	private function create_post( $content ) {
		return wp_insert_post(
			[
				'post_title' => 'replace shortcode test',
				'post_name' => 'replace-shortcode-test-' . wp_generate_password( 8, false ),
				'post_content' => $content,
				'post_status' => 'publish',
				'post_type' => 'post',
			]
		);
	}

	private function get_content( $post_id ) {
		clean_post_cache( $post_id );

		return get_post( $post_id )->post_content;
	}

	private function replace_all( $search, $replacement, array $flags ) {
		return $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
				'searchPhrase' => $search,
				'searchFlags' => $flags,
				'page' => 0,
				'perPage' => 25,
				'save' => true,
				'action' => 'replace',
				'replacement' => $replacement,
			],
			'POST'
		);
	}

	public function testReplaceAllLeavesExistingShortcodes() {
		$this->setNonce();

		$post_id = $this->create_post( self::CONTENT );
		$result = $this->replace_all( 'zebra', 'horse', [ 'case' ] );

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertSame( 'a horse [upper]keep[/upper] [date format="Y"] and a horse', $this->get_content( $post_id ) );
	}

	public function testReplaceAllProcessesShortcodesInReplacement() {
		$this->setNonce();

		$post_id = $this->create_post( self::CONTENT );
		$result = $this->replace_all( 'zebra', '[upper]horse[/upper]', [ 'case' ] );

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertSame( 'a HORSE [upper]keep[/upper] [date format="Y"] and a HORSE', $this->get_content( $post_id ) );
	}

	public function testRegexReplaceAllProcessesCapturesInShortcodes() {
		$this->setNonce();

		$post_id = $this->create_post( self::CONTENT );
		$result = $this->replace_all( 'a (zeb)ra', '[upper]$1[/upper]', [ 'regex' ] );

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertSame( 'ZEB [upper]keep[/upper] [date format="Y"] and ZEB', $this->get_content( $post_id ) );
	}

	public function testSingleReplaceOnlyProcessesShortcodesInReplacement() {
		$this->setNonce();

		$post_id = $this->create_post( self::CONTENT );
		$result = $this->callApi(
			'source/posts/row/' . $post_id,
			[
				'source' => [ 'posts' ],
				'searchPhrase' => 'zebra',
				'searchFlags' => [],
				'replacement' => [
					'source' => 'posts',
					'column' => 'post_content',
					'operation' => 'replace',
					'searchValue' => 'zebra',
					'replaceValue' => '[upper]horse[/upper]',
					'posId' => 2,
				],
			],
			'POST'
		);

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertSame( 'a HORSE [upper]keep[/upper] [date format="Y"] and a zebra', $this->get_content( $post_id ) );
	}

	public function testSetStillProcessesShortcodes() {
		$this->setNonce();

		$post_id = $this->create_post( self::CONTENT );
		$result = $this->callApi(
			'source/posts/row/' . $post_id,
			[
				'source' => [ 'posts' ],
				'searchPhrase' => 'zebra',
				'searchFlags' => [],
				'replacement' => [
					'source' => 'posts',
					'column' => 'post_content',
					'operation' => 'set',
					'replaceValue' => '[upper]horse[/upper]',
				],
			],
			'POST'
		);

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertSame( 'HORSE', $this->get_content( $post_id ) );
	}
}
