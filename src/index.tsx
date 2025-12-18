import { createRoot } from 'react-dom/client';
import { setDefaultLocale } from 'react-datepicker';
import App from './app';
import './style.scss';

function show( dom: string ): void {
	const element = document.getElementById( dom );
	if ( element ) {
		const root = createRoot( element );
		root.render( <App /> );
	}
}

if ( document.querySelector( '#react-ui' ) ) {
	const migrate = document.querySelector( '.jquery-migrate-deprecation-notice' );

	if ( migrate ) {
		migrate.remove();
	}

	setDefaultLocale( ( window as any ).SearchRegexi10n.locale.replace( '_', '' ) );
	show( 'react-ui' );
}

( window as any ).searchregex = ( window as any ).SearchRegexi10n.version;
