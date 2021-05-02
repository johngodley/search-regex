<?php

namespace SearchRegex;

class Match_Context_Replace extends Match_Context_Value {
	const TYPE_REPLACE = 'replace';

	private $replacement = null;
	private $replacement_label = null;

	public function set_replacement( $value, $label = null ) {
		$this->replacement = $value;
		$this->replacement_label = $label ? $label : $value;
	}

	public function get_type() {
		return self::TYPE_REPLACE;
	}

	public function get_replacement() {
		return $this->replacement;
	}

	public function needs_saving() {
		return true;
	}

	public function to_json() {
		return array_merge( parent::to_json(), [
			'replacement' => $this->restrict_value( $this->replacement ),
			'replacement_label' => $this->restrict_value( $this->replacement_label ),
		] );
	}

	public function is_equal( Match_Context $context ) {
		if ( parent::is_equal( $context ) ) {
			return $this->replacement === $context->replacement;
		}

		return false;
	}
}
