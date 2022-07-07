/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

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
