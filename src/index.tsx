import { createRoot } from 'react-dom/client';
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

	// ✨ Locale handling moved to DatePicker component
	// No need for react-datepicker locale setup
	show( 'react-ui' );
}

( window as any ).searchregex = SearchRegexi10n.version;
