/**
 * External dependencies
 */

import React from 'react';
import ClickOutside from '../click-outside';


/**
 * onClose callback.
 *
 * @callback requestCallback
 * @param {Object} ev Event handler object
 */

/**
 * The modal content.
 *
 * @param {{onClose: requestCallback}} props - Provide the URL and child components
 */
function ModalContent( { onClose, children } ) {
	return (
		<ClickOutside className="wpl-click-outside" onOutside={ onClose }>
			<div className="wpl-modal_content">
				<div className="wpl-modal_close">
					<button onClick={ onClose }>&#x2716;</button>
				</div>

				{ children }
			</div>
		</ClickOutside>
	);
}

export default ModalContent;
