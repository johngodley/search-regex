/**
 * External dependencies
 */

import React, { useRef, useMemo } from 'react';

/**
 * Local dependencies
 */

import { getAdjustedPosition, adjustArrowStyle, TogglePosition, DropdownPosition } from './dimensions';
import PopoverArrow from './arrow';

/**
 * The actual popover content.
 *
 * @param {object} props - Component props.
 * @param {string} props.align - Our alignment.
 * @param {boolean} props.hasArrow - Show an arrow or not
 * @param {TogglePosition|null} props.togglePosition - The toggle position.
 * @param {DropdownPosition|null} props.position - Our position.
 * @param {object} props.children - Child components to show inside the popover.
 */
function Popover( props ) {
	const { position, children, togglePosition, align, hasArrow } = props;
	const popoverRef = useRef( null );
	const style = useMemo(
		() =>
			getAdjustedPosition(
				position,
				togglePosition,
				align,
				popoverRef,
				hasArrow
			),
		[ position, togglePosition ]
	);
	const arrowStyle = useMemo(
		() => adjustArrowStyle( style, popoverRef.current ),
		[ style ]
	);

	return (
		<>
			{ hasArrow && (
				<PopoverArrow style={ arrowStyle } align={ align } />
			) }

			<div
				className="wpl-popover__content"
				style={ { ...style, visibility: position && position.left ? 'visible' : 'hidden' } }
				ref={ popoverRef }
			>
				{ children }
			</div>
		</>
	);
}

export default Popover;
