/**
 * External dependencies
 */

import React, { useState, useRef, useEffect } from 'react';
import { createPortal } from 'react-dom';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import Popover from './popover';
import ClickOutside from 'component/click-outside';
import { PORTAL, getPosition, getDimensions } from './dimensions';

function Dropdown( props ) {
	const { renderContent, className, renderToggle, align = 'left', onHide, matchToggle = false, hasArrow = false, offset } = props;
	const [ isShowing, setShowing ] = useState( false );
	const [ togglePosition, setTogglePosition ] = useState( null );
	const toggleRef = useRef( null );
	const hide = () => {
		setShowing( false );
		onHide && onHide();
	};

	// Update the sizes when matchToggle changes
	useEffect( () => {
		if ( isShowing ) {
			setTogglePosition( getDimensions( toggleRef.current, offset ) );
		}
	}, [ isShowing, matchToggle ] );

	// Close popover when window resized
	useEffect( () => {
		if ( ! isShowing ) {
			return;
		}

		const listener = window.addEventListener( 'resize', () => {
			setShowing( false );
		} );

		return () => {
			window.removeEventListener( 'resize', listener );
		}
	}, [ isShowing ] );

	return (
		<>
			<div className={ classnames( 'redirect-popover__toggle', className ) } ref={ toggleRef }>
				{ renderToggle( isShowing, ( ev ) => setShowing( ! isShowing ) ) }
			</div>

			{ isShowing && createPortal(
				<ClickOutside className="redirect-popover" onOutside={ hide }>
					<Popover position={ getPosition( togglePosition, matchToggle ) } togglePosition={ togglePosition } align={ align } hasArrow={ hasArrow }>
						{ renderContent( () => setShowing( false ) ) }
					</Popover>
				</ClickOutside>,
				document.getElementById( PORTAL )
			) }
		</>
	);
}

export default Dropdown;
