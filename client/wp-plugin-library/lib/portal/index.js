import { DROPDOWN_PORTAL, PORTAL_WRAPPER } from '../../constant';

/**
 * Get a portal node, or create it if it doesn't exist.
 *
 * @param {string} portalName DOM ID of the portal
 * @returns {Element}
 */
export default function getPortal( portalName ) {
	let portal = document.getElementById( portalName );

	if ( portal === null ) {
		const wrapper = document.getElementById( PORTAL_WRAPPER );

		portal = document.createElement( 'div' );

		if ( wrapper && wrapper.parentNode ) {
			portal.setAttribute( 'id', DROPDOWN_PORTAL );
			wrapper.parentNode.appendChild( portal );
		}
	}

	return portal;
}
