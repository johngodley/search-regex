/**
 * External dependencies
 */

import { useEffect, useRef } from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */
import { getWordPressUrl } from 'wp-plugin-lib/wordpress-url';
import { getPluginPage } from 'lib/plugin';

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

		if ( previousPage.current ) {
			history.pushState( {}, '', getWordPressUrl( { sub: page }, { sub: 'search' }, '?page=search-regex.php' ) );
		}

		previousPage.current = page;
	}, [ page ]);

	return children;
}

export default PageRouter;
