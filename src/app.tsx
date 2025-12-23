import { StrictMode, useEffect } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import Home from './page/home';
import { apiFetch } from '@wp-plugin-lib';
import { queryClient } from './lib/query-client';
import { useSearchStore } from './stores/search-store';
import getPreload from './lib/preload';
import type { PresetValue } from './types/preset';

// Validate the locale works with the browser
try {
	new Intl.NumberFormat( SearchRegexi10n.locale );
} catch {
	SearchRegexi10n.locale = 'en-US';
}

// Set API nonce and root URL
apiFetch.resetMiddlewares();
apiFetch.use( apiFetch.createRootURLMiddleware( SearchRegexi10n?.api?.WP_API_root ?? '/wp-json/' ) );
apiFetch.use( apiFetch.createNonceMiddleware( SearchRegexi10n?.api?.WP_API_nonce ?? '' ) );

// Get preloaded presets for SearchStore initialization
const preloadedPresets = getPreload< PresetValue[] >( 'presets', [] );

function AppInitializer( { children }: { children: React.ReactNode } ) {
	const initialize = useSearchStore( ( state ) => state.initialize );

	useEffect( () => {
		// Initialize with presets - this will merge query params + presets properly
		// Query params are already used in initial state, but this ensures presets are applied
		initialize( preloadedPresets );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] ); // Only run once on mount

	return <>{ children }</>;
}

const App = (): JSX.Element => (
	<QueryClientProvider client={ queryClient }>
		<AppInitializer>
			<StrictMode>
				<Home />
			</StrictMode>
		</AppInitializer>
		{ process.env.NODE_ENV === 'development' && <ReactQueryDevtools initialIsOpen={ false } /> }
	</QueryClientProvider>
);

export default App;
