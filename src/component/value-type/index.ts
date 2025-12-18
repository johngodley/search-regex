import { __ } from '@wordpress/i18n';

export default function getValueType( type: string ): string | null {
	if ( type === 'php' ) {
		return __( 'Serialized PHP', 'search-regex' );
	}

	if ( type === 'json' ) {
		return __( 'JSON', 'search-regex' );
	}

	if ( type === 'blocks' ) {
		return __( 'Blocks', 'search-regex' );
	}

	if ( type === 'html' ) {
		return __( 'HTML', 'search-regex' );
	}

	return null;
}
