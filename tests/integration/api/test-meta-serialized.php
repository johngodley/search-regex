<?php

/**
 * Meta values are written directly to the database, so a serialized value must never be saved - WordPress
 * would unserialize it the next time the meta is read.
 */
class MetaSerializedApiTest extends SearchRegex_Api_Test {
	const PAYLOAD = 'O:8:"stdClass":0:{}';

	private function create_meta( $value ) {
		global $wpdb;

		$post_id = wp_insert_post(
			[
				'post_title' => 'meta serialized test',
				'post_status' => 'publish',
				'post_type' => 'post',
			]
		);
		add_post_meta( $post_id, 'searchregex_test', $value );

		$meta_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s", $post_id, 'searchregex_test' ) );

		return [ $post_id, $meta_id ];
	}

	private function save_row( $meta_id, array $replacement ) {
		return $this->callApi(
			'source/post-meta/row/' . $meta_id,
			[
				'source' => [ 'post-meta' ],
				'searchPhrase' => 'plainvalue',
				'searchFlags' => [],
				'replacement' => array_merge(
					[
						'source' => 'post-meta',
						'column' => 'meta_value',
					],
					$replacement
				),
			],
			'POST'
		);
	}

	private function assertMetaUnchanged( $post_id ) {
		wp_cache_flush();

		$this->assertSame( 'plainvalue', get_post_meta( $post_id, 'searchregex_test', true ) );
	}

	public function testSetCannotSaveSerializedValue() {
		$this->setNonce();

		[ $post_id, $meta_id ] = $this->create_meta( 'plainvalue' );
		$result = $this->save_row( $meta_id, [ 'operation' => 'set', 'replaceValue' => self::PAYLOAD ] );

		$this->assertEquals( 400, $result->status );
		$this->assertMetaUnchanged( $post_id );
	}

	public function testReplaceCannotSaveSerializedValue() {
		$this->setNonce();

		[ $post_id, $meta_id ] = $this->create_meta( 'plainvalue' );
		$result = $this->save_row(
			$meta_id,
			[
				'operation' => 'replace',
				'searchValue' => 'plainvalue',
				'replaceValue' => self::PAYLOAD,
				'posId' => 0,
			]
		);

		$this->assertEquals( 400, $result->status );
		$this->assertMetaUnchanged( $post_id );
	}

	public function testSetStillSavesPlainValue() {
		$this->setNonce();

		[ $post_id, $meta_id ] = $this->create_meta( 'plainvalue' );
		$result = $this->save_row( $meta_id, [ 'operation' => 'set', 'replaceValue' => 'newvalue' ] );

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );

		wp_cache_flush();
		$this->assertSame( 'newvalue', get_post_meta( $post_id, 'searchregex_test', true ) );
	}
}
