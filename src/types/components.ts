/**
 * Multi-option value used in multi-select dropdowns
 */
export interface MultiOptionValue {
	label: string;
	value: string;
}

/**
 * Multi-option group value
 */
export interface MultiOptionGroupValue {
	label: string;
	value: string;
	options?: MultiOptionValue[];
	multiple?: boolean;
}

/**
 * Menu item used in navigation menus
 */
export interface MenuItem {
	name: string;
	value: string;
}

/**
 * Button option for dropdown buttons
 */
export interface ButtonOption {
	name: string;
	title: string;
}
