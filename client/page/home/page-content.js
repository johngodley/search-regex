/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */
import Options from 'page/options';
import Support from 'page/support';
import PresetManagement from 'page/preset-management';
import SearchReplace from 'page/search-replace';

function PageContent( { page } ) {
	switch ( page ) {
		case 'support':
			return <Support />;

		case 'options':
			return <Options />;

		case 'presets':
			return <PresetManagement />;
	}

	return <SearchReplace />;
}

export default PageContent;
