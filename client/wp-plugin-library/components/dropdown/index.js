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
import ClickOutside from '../click-outside';
import { getPosition, getDimensions } from './dimensions';
import { DROPDOWN_PORTAL, PORTAL_WRAPPER } from '../constant';

/**
 * Render callback.
 *
 * @callback contentRender
 * @param {toggleCallback} toggle
 */

/**
 * Toggle callback.
 *
 * @callback toggleCallback
 */

/**
 * Render callback.
 *
 * @callback toggleRender
 * @param {boolean} isShowing Is the menu currently visible?
 * @param {toggleCallback} toggle Toggle the dropdown on/off.
 */

function createDropdownPortal() {
	if ( document.querySelector( DROPDOWN_PORTAL ) === null ) {
		const portal = document.createElement( 'div' );
		const wrapper = document.getElementById( PORTAL_WRAPPER );

		if ( wrapper && wrapper.parentNode ) {
			portal.setAttribute( 'id', DROPDOWN_PORTAL );
			wrapper.parentNode.appendChild( portal );
		}
	}
}

/**
 * Displays a dropdown - a toggle that when clicked shows a dropdown area.
 *
 * @param {object} props - Component props.
 * @param {string} [props.className] - Additional class name.
 * @param {string} [props.align='left'] - Align the dropdown on the `left` or `right`.
 * @param {number} [props.widthAdjust=0] - Change the dropdown menu to match the width of the toggle.
 * @param {boolean} [props.hasArrow=false] - Show a small arrow pointing at the toggle when the dropdown is toggled.
 * @param {number} [props.offset] - Mysterious value to offset the toggle by.
 * @param {toggleCallback} [props.onHide] - Callback when the dropdown is hidden.
 * @param {contentRender} props.renderContent - Called when the dropdown menu should be shown
 * @param {toggleRender} props.renderToggle - Called to display the toggle.
 */
function Dropdown( props ) {
	const {
		renderContent,
		className,
		renderToggle,
		align = 'left',
		onHide,
		widthAdjust = -1,
		hasArrow = false,
		offset = 0,
	} = props;
	const [ isShowing, setShowing ] = useState( false );
	const [ togglePosition, setTogglePosition ] = useState( null );
	const toggleRef = useRef( null );
	const onResize = () => setShowing( false );
	const hide = () => {
		setShowing( false );
		onHide && onHide();
	};

	// Create the portal
	useEffect( createDropdownPortal, [] );

	// Update the sizes when matchToggle changes
	useEffect( () => {
		if ( isShowing ) {
			setTogglePosition( getDimensions( toggleRef.current, offset ) );
		}
	}, [ isShowing, widthAdjust ]);

	// Close popover when window resized
	useEffect( () => {
		if ( ! isShowing ) {
			return;
		}

		window.addEventListener( 'resize', onResize );

		return () => {
			window.removeEventListener( 'resize', onResize );
		};
	}, [ isShowing ]);

	const portal = document.getElementById( DROPDOWN_PORTAL );
	return (
		<>
			<div className={ classnames( 'wpl-popover__toggle', className ) } ref={ toggleRef }>
				{ renderToggle( isShowing, () => setShowing( ! isShowing ) ) }
			</div>

			{ isShowing &&
				portal &&
				createPortal(
					<ClickOutside className="wpl-popover" onOutside={ hide }>
						<Popover
							position={ getPosition( togglePosition, widthAdjust !== -1 ) }
							togglePosition={ togglePosition }
							align={ align }
							hasArrow={ hasArrow }
						>
							{ renderContent( () => setShowing( false ) ) }
						</Popover>
					</ClickOutside>,
					portal
				) }
		</>
	);
}

export default Dropdown;
