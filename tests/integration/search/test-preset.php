<?php

use SearchRegex\Search;

class PresetTest extends WP_UnitTestCase {
	public function testEmptyPreset() {
		$expected = [
			'name' => '',
			'description' => '',
			'search' => [
				'searchPhrase' => '',
				'replacement' => '',
				'perPage' => 25,
				'searchFlags' => [],
				'source' => [],
				'filters' => [],
				'view' => [],
				'action' => 'nothing',
			],
			'locked' => [],
			'tags' => [],
		];

		$preset = new Search\Preset();
		$actual = $preset->to_json();
		unset( $actual['id'] );

		$this->assertEquals( $expected, $actual );
	}

	public function testSetGoodParams() {
		$params = [
			'name' => 'cat',
			'description' => 'dog',
			'search' => [
				'searchPhrase' => 'cat',
				'replacement' => 'dog',
				'perPage' => 100,
				'searchFlags' => [ 'case' ],
				'source' => [ 'comment' ],
			],
			'locked' => [ 'perPage' ],
			'tags' => [
				[
					'title' => '<strong>bad中"\'</strong>',
					'name' => '<strong>bad中"\'</strong>',
				],
			],
		];

		$preset = new Search\Preset( $params );
		$actual = $preset->to_json();

		unset( $actual['id'] );
		$params['tags'] = [ [ 'title' => 'bad中"\'', 'name' => 'bad中"\'' ] ];
		$params['search']['filters'] = [];
		$params['search']['view'] = [];
		$params['search']['action'] = 'replace';
		$params['search']['actionOption'] = [];

		$this->assertEquals( $params, $actual );
	}

	public function testBadName() {
		$params = [ 'name' => '<strong>bad中"\'</strong>' ];
		$preset = new Search\Preset( $params );

		$this->assertEquals( 'bad中"\'', $preset->to_json()['name'] );
	}

	public function testBadDescription() {
		$params = [ 'description' => '<strong>bad中"\'</strong>' ];
		$preset = new Search\Preset( $params );

		$this->assertEquals( 'bad中"\'', $preset->to_json()['description'] );
	}

	public function testBadPerPage() {
		$preset = new Search\Preset( [ 'search' => [ 'perPage' => 'cat' ] ] );
		$this->assertEquals( 25, $preset->to_json()['search']['perPage'] );

		$preset = new Search\Preset( [ 'search' => [ 'perPage' => 5001 ] ] );
		$this->assertEquals( 5000, $preset->to_json()['search']['perPage'] );

		$preset = new Search\Preset( [ 'search' => [ 'perPage' => 5 ] ] );
		$this->assertEquals( 25, $preset->to_json()['search']['perPage'] );
	}

	public function testBadSource() {
		$preset = new Search\Preset( [ 'search' => [ 'source' => 5 ] ] );
		$this->assertEquals( [], $preset->to_json()['search']['source'] );

		$preset = new Search\Preset( [ 'search' => [ 'source' => 'cat' ] ] );
		$this->assertEquals( [], $preset->to_json()['search']['source'] );
	}

	public function testBadSearchFlags() {
		$preset = new Search\Preset( [ 'search' => [ 'searchFlags' => 'cat' ] ] );
		$this->assertEquals( [], $preset->to_json()['search']['searchFlags'] );

		$preset = new Search\Preset( [ 'search' => [ 'searchFlags' => [ 'cat' ] ] ] );
		$this->assertEquals( [], $preset->to_json()['search']['searchFlags'] );
	}

	public function testBadLocked() {
		$preset = new Search\Preset( [ 'locked' => 5 ] );
		$this->assertEquals( [], $preset->to_json()['locked'] );

		$preset = new Search\Preset( [ 'locked' => [ 'cat' ] ] );
		$this->assertEquals( [], $preset->to_json()['locked'] );
	}

	public function testBadTags() {
		$preset = new Search\Preset( [ 'tags' => 5 ] );
		$this->assertEquals( [], $preset->to_json()['tags'] );

		$preset = new Search\Preset( [ 'tags' => [ 'cat' ] ] );
		$this->assertEquals( [], $preset->to_json()['tags'] );

		$preset = new Search\Preset( [ 'tags' => [ [ 'cat' ] ] ] );
		$this->assertEquals( [], $preset->to_json()['tags'] );
	}

	public function testDuplicateTags() {
		$tag = [ 'name' => 'name', 'title' => 'title' ];
		$preset = new Search\Preset( [ 'tags' => [ $tag, $tag ] ] );
		$this->assertEquals( [ $tag ], $preset->to_json()['tags'] );
	}

	public function testCreate() {
		delete_option( Search\Preset::OPTION_NAME );

		$preset = new Search\Preset( [ 'name' => 'cat' ] );
		$created = $preset->create();

		$get = Search\Preset::get( $preset->get_id() );

		$this->assertEquals( $get->to_json(), $preset->to_json() );
	}

	public function testUpdate() {
		delete_option( Search\Preset::OPTION_NAME );

		$preset = new Search\Preset( [ 'name' => 'cat' ] );
		$created = $preset->create();
		$preset->update( [ 'name' => 'dog' ] );

		$get = Search\Preset::get( $preset->get_id() );

		$this->assertEquals( $get->to_json(), $preset->to_json() );
	}

	public function testDelete() {
		delete_option( Search\Preset::OPTION_NAME );

		$preset = new Search\Preset( [ 'name' => 'cat' ] );
		$created = $preset->create();
		$preset->delete();

		$get = Search\Preset::get( $preset->get_id() );

		$this->assertEquals( null, $get );
	}
}
