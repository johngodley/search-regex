<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context for a keyvalue pair
 */
class Pair extends Context\Context {
	const TYPE_PAIR = 'keyvalue';

	/**
	 * Key
	 *
	 * @var Context\Context
	 * @readonly
	 */
	private $key;

	/**
	 * Value
	 *
	 * @var Context\Context
	 * @readonly
	 */
	private $value;

	public function __construct( Context\Context $key, Context\Context $value ) {
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Get the key
	 *
	 * @return Context\Context
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the value
	 *
	 * @return Context\Context
	 */
	public function get_value() {
		return $this->value;
	}

	public function get_type() {
		return self::TYPE_PAIR;
	}

	public function to_json() {
		$key = $this->key->to_json();
		$value = $this->value->to_json();

		unset( $key['context_id'] );
		unset( $value['context_id'] );

		return array_merge( parent::to_json(), [
			'key' => $key,
			'value' => $value,
		] );
	}

	public function is_equal( Context\Context $context ) {
		if ( parent::is_equal( $context ) && $context instanceof Context\Type\Pair ) {
			return $this->key->is_equal( $context->key ) && $this->value->is_equal( $context->value );
		}

		return false;
	}

	public function is_matched() {
		return $this->key->is_matched() || $this->value->is_matched();
	}

	public function needs_saving() {
		return $this->key->needs_saving() || $this->value->needs_saving();
	}
}
