/**
 * External dependencies
 */

import React from 'react';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * onClick callback.
 *
 * @callback clickCallback
 * @param {Object} ev Event handler object
 */

/**
 * onCancel callback.
 *
 * @callback cancelCallback
 * @param {Object} ev Event handler object
 */

/**
 * A small badge, used to indicate a status. Can show a close button to remove the badge.
 *
 * @param {Object} props - Component props
 * @param {Object} props.children - Child components
 * @param {String} props.className - Class name for the wrapper
 * @param {clickCallback} props.onClick - Callback when user clicks outside of the wrapper
 * @param {string} props.title - Callback when user clicks outside of the wrapper
 * @param {cancelCallback} props.onCancel - Callback when user clicks outside of the wrapper
 * @param {boolean} props.disabled - Callback when user clicks outside of the wrapper
 */
const Badge = ( props ) => {
	const { children, className, onClick, title, onCancel, disabled } = props;
	const extra = {
		title,
		onClick,
	};
	const cancel = ( ev ) => {
		ev.preventDefault();
		! disabled && onCancel( ev );
	};

	return (
		<div className={ classnames( 'wpl-badge', className, onClick ? 'wpl-badge__click' : null ) } { ...extra }>
			<div>
				{ children }
				{ onCancel && <span onClick={ cancel }>⨯</span> }
			</div>
		</div>
	);
};

export default Badge;
