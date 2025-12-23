import { useEffect, useRef, ReactNode } from 'react';
import { getWordPressUrl } from '@wp-plugin-lib';
import { getPluginPage } from '../../lib/plugin';

interface PageRouterProps {
	page: string;
	setPage: ( page: string ) => void;
	children: ReactNode;
	onPageChange: () => void;
}

function PageRouter( props: PageRouterProps ) {
	const { page, setPage, children, onPageChange } = props;
	const previousPage = useRef< string | undefined >( undefined );

	useEffect( () => {
		function onPageChanged() {
			const currentPage = getPluginPage( '' );

			setPage( currentPage );
		}

		window.addEventListener( 'popstate', onPageChanged );

		return () => {
			window.removeEventListener( 'popstate', onPageChanged );
		};
	}, [ setPage ] );

	useEffect( () => {
		if ( previousPage.current === undefined ) {
			previousPage.current = page;
			return;
		}

		if ( previousPage.current && previousPage.current !== page ) {
			onPageChange();
			history.pushState( {}, '', getWordPressUrl( { sub: page }, { sub: 'search' }, '?page=search-regex.php' ) );
		}

		previousPage.current = page;
	}, [ page, onPageChange ] );

	return children;
}

export default PageRouter;
