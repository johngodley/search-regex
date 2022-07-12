/**
 * External dependencies
 */

import { useEffect, useRef } from 'react';

/**
 * Internal dependencies
 */
import { getWordPressUrl } from '@wp-plugin-lib';
import { getPluginPage } from '../../lib/plugin';

function PageRouter( props ) {
	const { page, setPage, children, onPageChange } = props;
	const previousPage = useRef();

	function onPageChanged() {
		const page = getPluginPage();

		setPage( page );
	}

	useEffect(() => {
		window.addEventListener( 'popstate', onPageChanged );

		return () => {
			window.removeEventListener( 'popstate', onPageChanged );
		};
	}, []);

	useEffect(() => {
		onPageChange();

		if ( previousPage.current && previousPage.current !== page) {
			history.pushState( {}, '', getWordPressUrl( { sub: page }, { sub: 'search' }, '?page=search-regex.php' ) );
		}

		previousPage.current = page;
	}, [ page ]);

	return children;
}

export default PageRouter;
