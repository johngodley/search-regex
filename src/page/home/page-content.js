/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Options from '../options';
import Support from '../support';
import PresetManagement from '../preset-management';
import SearchReplace from '../search-replace';

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
