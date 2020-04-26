/**
 * External dependencies
 */

import React, { useRef, useMemo } from 'react';

/**
 * Local dependencies
 */

import { getAdjustedPosition, adjustArrowStyle } from './dimensions';
import PopoverArrow from './arrow';

function Popover( props ) {
	const { position, children, togglePosition, align, hasArrow } = props;
	const popoverRef = useRef( null );
	const style = useMemo( () => getAdjustedPosition( position, togglePosition, align, popoverRef, hasArrow ), [ position, togglePosition ] );
	const arrowStyle = useMemo( () => adjustArrowStyle( style, popoverRef.current ), [ style ] );

	return (
		<>
			{ hasArrow && <PopoverArrow style={ arrowStyle } align={ align } /> }

			<div className="redirect-popover__content" style={ style } ref={ popoverRef }>
				{ children }
			</div>
		</>
	);
}

export default Popover;
