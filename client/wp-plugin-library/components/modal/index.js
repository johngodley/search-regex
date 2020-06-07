/**
 * External dependencies
 */

import React from 'react';
import ReactDOM from 'react-dom';

/**
 * Internal dependencies
 */

import ModalWrapper from './wrapper';
import getPortal from '../../lib/portal';
import './style.scss';

/** @const {string} */
export const MODAL_PORTAL = 'wpl-modal';

/**
 * onClose callback.
 *
 * @callback closeCallback
 */

/**
 * Show a modal dialog, using the element `react-modal`.
 *
 * A global class `wpl-modal_shown` will be added to `body` when the modal is being shown, and removed after.
 *
 * @param {Object} props - Component props
 * @param {Boolean} props.padding - Include padding, defaults to `true`
 * @param {closeCallback} props.onClose - Function to call to close the modal
 */
const Modal = ( props ) => ReactDOM.createPortal( <ModalWrapper { ...props } />, getPortal( MODAL_PORTAL ) );

export default Modal;
