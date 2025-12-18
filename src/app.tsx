import { StrictMode } from 'react';
import { Provider } from 'react-redux';
import createReduxStore from './state';
import { getInitialState } from './state/initial';
import Home from './page/home';
import { apiFetch } from '@wp-plugin-lib';

// Validate the locale works with the browser
try {
	new Intl.NumberFormat( ( window as any ).SearchRegexi10n.locale as string );
} catch {
	( window as any ).SearchRegexi10n.locale = 'en-US';
}

// Set API nonce and root URL
apiFetch.resetMiddlewares();
apiFetch.use( apiFetch.createRootURLMiddleware( ( window as any ).SearchRegexi10n?.api?.WP_API_root ?? '/wp-json/' ) );
apiFetch.use( apiFetch.createNonceMiddleware( ( window as any ).SearchRegexi10n?.api?.WP_API_nonce ?? '' ) );

const App = (): JSX.Element => (
	<Provider store={ createReduxStore( getInitialState() ) }>
		<StrictMode>
			<Home />
		</StrictMode>
	</Provider>
);

export default App;
