import { WORDPRESS_WRAP } from '../constant';

/** @const {number} */
const OFFSET_MAX = 20;

/** @const {number} */
const OFFSET_ARROW = 5;
// /** @typedef {import('@wordpress/components').WPCompleter} WPCompleter */
/**
 * @typedef DropdownPosition
 * @type
 * @property {number} left - Left offset
 * @property {number} top - Top offset
 * @property {number} [width] - Optional width
 */

/**
 * @typedef TogglePosition
 * @type
 * @property {number} parentWidth - Width of the parent
 * @property {number} left - Left offset
 * @property {number} width - Width
 */

/**
 * Adjust the dropdown position based on the alignment.
 *
 * @param {number} leftPos - left position.
 * @param {number} popoverWidth - width of popover.
 * @param {number} toggleWidth - width of toggle button.
 * @param {string} align - alignment.
 */
function adjustForAlignment( leftPos, popoverWidth, toggleWidth, align ) {
	if ( align === 'right' ) {
		return leftPos + toggleWidth - popoverWidth;
	}

	if ( align === 'centre' ) {
		return leftPos - ( popoverWidth + toggleWidth ) / 2;
	}

	return leftPos;
}

/**
 * This is left aligned by default. Adjust to fit the alignment and screen size
 *
 * @param {DropdownPosition|null} position - The position.
 * @param {TogglePosition|null} togglePosition - The toggle position.
 * @param {string} align - Popover alignment.
 * @param {{current: HTMLElement|null}} ref - Our node.
 * @param {boolean} hasArrow - Show an arrow?
 */
export function getAdjustedPosition( position, togglePosition, align, ref, hasArrow ) {
	if ( position === null || togglePosition === null ) {
		return {};
	}

	if ( ! ref.current ) {
		return {
			...position,
			visibility: 'hidden', // Hide until ready otherwise we get a flicker
		};
	}

	const width = position.width ? position.width : ref.current.getBoundingClientRect().width;
	const minLeftPos = togglePosition.parentWidth - width - OFFSET_MAX;
	const adjustedLeft = adjustForAlignment(
		togglePosition.left,
		position.width ? position.width : width,
		togglePosition.width,
		align
	);

	return {
		...position,
		left: Math.min( minLeftPos, adjustedLeft ),
		top: hasArrow ? position.top + OFFSET_ARROW : position.top,
	};
}

/**
 * Get the dimensions of the node.
 *
 * @param {HTMLElement|null} ref - The dom node.
 * @param {number} offset - Our mysterious offset.
 */
export function getDimensions( ref, offset ) {
	const parentNode = document.getElementById( WORDPRESS_WRAP );
	if ( ref === null || parentNode === null ) {
		return {};
	}

	const parentRect = parentNode.getBoundingClientRect();
	const { height, width, left, top } = ref.getBoundingClientRect();

	return {
		left: left - parentRect.left + ( offset ? offset : 0 ),
		top: top - parentRect.top + 1,
		width,
		height,
		parentWidth: parentRect.width,
		parentHeight: parentRect.height,
	};
}

/**
 * Get the dropdown position.
 *
 * @param {ClientRect|null} togglePosition - Toggle client rect.
 * @param {boolean} matchToggle - Match the toggle width
 * @returns {DropdownPosition|null} Position
 */
export function getPosition( togglePosition, matchToggle ) {
	if ( togglePosition === null ) {
		return null;
	}

	const { left, top, width, height } = togglePosition;
	/** @type DropdownPosition */
	const position = {
		left: left,
		top: top + height,
	};

	if ( matchToggle ) {
		position.width = width;
	}

	return position;
}

/**
 * Adjust the arrow style.
 *
 * @param {object} style - Style object.
 * @param {HTMLElement|null} ref - DOM node.
 */
export function adjustArrowStyle( style, ref ) {
	if ( ref ) {
		return {
			...style,
			width: ref.getBoundingClientRect().width,
		};
	}

	return style;
}
