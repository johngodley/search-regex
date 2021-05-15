/**
 * External dependencies
 */

import { translate as __ } from 'i18n-calypso';

export default function getValueType( type ) {
	if ( type === 'php' ) {
		return __( 'Serialized PHP' );
	}

	if ( type === 'json' ) {
		return __( 'JSON' );
	}

	if ( type === 'blocks' ) {
		return __( 'Blocks' );
	}

	if ( type === 'html' ) {
		return __( 'HTML' );
	}

	return null;
}
