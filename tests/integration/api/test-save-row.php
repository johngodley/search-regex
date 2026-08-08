<?php

/**
 * Reproduces https://github.com/johngodley/search-regex/issues/254
 *
 * Replacing a single result (rather than doing a bulk "Replace All") can remove the last
 * occurrence of the search phrase from a row. When the plugin then reloads that row to build
 * the REST API response, it no longer matches the original search conditions, and the response
 * building must not crash.
 */
class SaveRowApiTest extends SearchRegex_Api_Test {
	private function create_post( $title ) {
		// Explicit post_name/post_content that don't contain the search phrase, so the only
		// match is in post_title - otherwise WP's auto-generated slug would also match, and
		// the row would still match the search overall even once post_title no longer does.
		return wp_insert_post(
			[
				'post_title' => $title,
				'post_name' => 'save-row-test-' . wp_generate_password( 8, false ),
				'post_content' => 'no matching phrase here',
				'post_status' => 'publish',
				'post_type' => 'post',
			]
		);
	}

	/**
	 * Find the pos_id of a match for a phrase in a column, the same way the client does before
	 * asking to replace a single, specific match.
	 */
	private function get_match_pos_id( $post_id, $search_phrase, $column ) {
		$search = $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
				'searchPhrase' => $search_phrase,
			],
			'POST'
		);

		foreach ( $search->data['results'] as $result ) {
			if ( (int) $result['row_id'] !== (int) $post_id ) {
				continue;
			}

			foreach ( $result['columns'] as $result_column ) {
				if ( $result_column['column_id'] !== $column ) {
					continue;
				}

				return $result_column['contexts'][0]['matches'][0]['pos_id'];
			}
		}

		return null;
	}

	/**
	 * A single-row replace that removes the only occurrence of the search phrase must not
	 * return a REST error - the row simply no longer matches the search.
	 */
	public function testReplacingOnlyMatchDoesNotError() {
		$this->setNonce();

		$post_id = $this->create_post( 'a cat sat on the mat' );
		$pos_id = $this->get_match_pos_id( $post_id, 'cat', 'post_title' );
		$this->assertNotNull( $pos_id );

		$result = $this->callApi(
			'source/posts/row/' . $post_id,
			[
				'source' => [ 'posts' ],
				'searchPhrase' => 'cat',
				'searchFlags' => [],
				'replacement' => [
					'source' => 'posts',
					'column' => 'post_title',
					'operation' => 'replace',
					'searchValue' => 'cat',
					'replaceValue' => 'dog',
					'posId' => $pos_id,
				],
			],
			'POST'
		);

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertNull( $result->data['result'] );

		// The row was changed in the database
		$this->assertEquals( 'a dog sat on the mat', get_post( $post_id )->post_title );
	}

	/**
	 * A single-row replace that leaves other occurrences of the search phrase in place should
	 * still return the updated row data, as before.
	 */
	public function testReplacingOneOfManyMatchesReturnsRow() {
		$this->setNonce();

		$post_id = $this->create_post( 'a cat sat with another cat' );
		$pos_id = $this->get_match_pos_id( $post_id, 'cat', 'post_title' );
		$this->assertNotNull( $pos_id );

		$result = $this->callApi(
			'source/posts/row/' . $post_id,
			[
				'source' => [ 'posts' ],
				'searchPhrase' => 'cat',
				'searchFlags' => [],
				'replacement' => [
					'source' => 'posts',
					'column' => 'post_title',
					'operation' => 'replace',
					'searchValue' => 'cat',
					'replaceValue' => 'dog',
					'posId' => $pos_id,
				],
			],
			'POST'
		);

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
		$this->assertNotNull( $result->data['result'] );
		$this->assertEquals( 'a dog sat with another cat', get_post( $post_id )->post_title );
	}
}
