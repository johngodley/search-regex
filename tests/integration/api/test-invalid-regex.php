<?php

/**
 * An invalid regular expression in a modify action fails for every row. It must be reported as an
 * error, and must never change the content.
 */
class InvalidRegexApiTest extends SearchRegex_Api_Test {
	private function search( $post_content, $save ) {
		$post_id = wp_insert_post(
			[
				'post_title' => 'invalid regex test',
				'post_name' => 'invalid-regex-test-' . wp_generate_password( 8, false ),
				'post_content' => $post_content,
				'post_status' => 'publish',
				'post_type' => 'post',
			]
		);

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
				'searchPhrase' => 'zebra',
				'searchFlags' => [ 'case' ],
				'page' => 0,
				'perPage' => 25,
				'save' => $save,
				'action' => 'modify',
				'actionOption' => [
					[
						'source' => 'posts',
						'column' => 'post_content',
						'operation' => 'replace',
						'searchValue' => '(crossing',
						'replaceValue' => 'x',
						'searchFlags' => [ 'regex' ],
					],
				],
			],
			'POST'
		);

		clean_post_cache( $post_id );

		return [ $result, get_post( $post_id )->post_content ];
	}

	public function testInvalidRegexIsReportedWhenSaving() {
		$this->setNonce();

		[ $result, $content ] = $this->search( 'a zebra crossing', true );

		$this->assertEquals( 400, $result->status );
		$this->assertSame( 'a zebra crossing', $content );
	}

	public function testInvalidRegexIsReportedWhenPreviewing() {
		$this->setNonce();

		[ $result, $content ] = $this->search( 'a zebra crossing', false );

		$this->assertEquals( 400, $result->status );
		$this->assertSame( 'a zebra crossing', $content );
	}
}
