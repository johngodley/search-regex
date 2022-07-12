<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context that contains a value.
 */
class Value extends Context\Context {
	const TYPE_VALUE = 'value';
	const MAX_LENGTH = 200;

	/**
	 * The value
	 *
	 * @var string|integer
	 * @readonly
	 */
	protected $value;

	/**
	 * Type of the value
	 *
	 * @var string
	 */
	protected $value_type;

	/**
	 * Label for the value, if appropriate.
	 *
	 * @var string
	 */
	protected $value_label;

	/**
	 * Value length. If the value has been cropped this will be longer than the length in `value`
	 *
	 * @var integer
	 */
	protected $value_length;

	/**
	 * Constructor
	 *
	 * @param string|integer $value Value.
	 * @param string         $label Label.
	 */
	public function __construct( $value, $label = null ) {
		parent::__construct();

		$this->value_type = Context\Value_Type::get( (string) $value );
		$this->value = "$value";
		$this->value_label = $label === null ? (string) $value : $label;
		$this->value_length = strlen( (string) $value );
	}

	/**
	 * Restrict the value to the max length
	 *
	 * @param string $value Value to restrict.
	 * @return string
	 */
	protected function restrict_value( $value ) {
		return mb_substr( $value, 0, self::MAX_LENGTH );
	}

	public function get_type() {
		return self::TYPE_VALUE;
	}

	/**
	 * Get value
	 *
	 * @return string|integer
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Get value type
	 *
	 * @return string
	 */
	public function get_value_type() {
		return $this->value_type;
	}

	public function is_equal( Context\Context $context ) {
		if ( parent::is_equal( $context ) && $context instanceof Context\Type\Value ) {
			return $this->value === $context->value;
		}

		return false;
	}

	/**
	 * Convert the Context\Type\Text to to_json
	 *
	 * @return array JSON
	 */
	public function to_json() {
		return array_merge( parent::to_json(), [
			'value' => $this->restrict_value( (string) $this->value ),
			'value_type' => $this->value_type,
			'value_label' => $this->restrict_value( $this->value_label ),
			'value_length' => $this->value_length,
		] );
	}
}
