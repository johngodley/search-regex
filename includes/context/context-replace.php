<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context when something has been replaced
 */
class Replace extends Context\Type\Value {
	const TYPE_REPLACE = 'replace';

	/**
	 * Replacement value
	 *
	 * @var string|integer|null
	 */
	private $replacement = null;

	/**
	 * Replacement value type
	 *
	 * @var string|null
	 */
	private $replacement_label = null;

	/**
	 * Set the replacement
	 *
	 * @param string|integer $value Value.
	 * @param string         $label Label.
	 * @return void
	 */
	public function set_replacement( $value, $label = null ) {
		$this->replacement = $value;
		$this->replacement_label = $label ? $label : (string) $value;
	}

	public function get_type() {
		return self::TYPE_REPLACE;
	}

	/**
	 * Get the replacement value
	 *
	 * @return string|null|integer
	 */
	public function get_replacement() {
		return $this->replacement;
	}

	public function is_matched() {
		return true;
	}

	public function needs_saving() {
		return true;
	}

	public function to_json() {
		return array_merge( parent::to_json(), [
			'replacement' => $this->restrict_value( $this->replacement === null ? '' : (string) $this->replacement ),
			'replacement_label' => $this->restrict_value( $this->replacement_label === null ? '' : $this->replacement_label ),
		] );
	}

	public function is_equal( Context\Context $context ) {
		if ( parent::is_equal( $context ) && $context instanceof Context\Type\Replace ) {
			return $this->replacement === $context->replacement;
		}

		return false;
	}
}
