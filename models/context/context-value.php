<?php

namespace SearchRegex;

class Match_Context_Value extends Match_Context {
	const TYPE_VALUE = 'value';
	const MAX_LENGTH = 200;

	protected $value;
	protected $value_type;
	protected $value_label;
	protected $value_length;

	public function __construct( $value, $label = null ) {
		parent::__construct();

		$this->value_type = Value_Type::get( $value );
		$this->value = "$value";
		$this->value_label = $label === null ? $value : $label;
		$this->value_length = strlen( $value );
	}

	protected function restrict_value( $value ) {
		return mb_substr( $value, 0, self::MAX_LENGTH );
	}

	public function get_type() {
		return self::TYPE_VALUE;
	}

	public function get_value() {
		return $this->value;
	}

	public function get_value_type() {
		return $this->value_type;
	}

	public function is_matched() {
		return false;
	}

	public function is_equal( Match_Context $context ) {
		if ( parent::is_equal( $context ) ) {
			return $this->value === $context->value;
		}

		return false;
	}

	/**
	 * Convert the Match_Context_String to to_json
	 *
	 * @return Array{context_id: int, context: string|null, matches: array, match_count: int} JSON
	 */
	public function to_json() {
		return array_merge( parent::to_json(), [
			'value' => $this->restrict_value( $this->value ),
			'value_type' => $this->value_type,
			'value_label' => $this->restrict_value( $this->value_label ),
			'value_length' => $this->value_length,
		] );
	}
}
