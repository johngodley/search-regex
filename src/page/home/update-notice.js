/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from 'react-redux'

/**
 * Internal dependencies
 */
import { ExternalLink, Notice, Button, createInterpolateElement } from '@wp-plugin-components';
import { saveSettings } from '../../state/settings/action';
import { CAP_SEARCHREGEX_OPTIONS, has_capability } from '../../lib/capabilities';

function UpdateNotice() {
	const { update_notice = false } = SearchRegexi10n;
	const dispatch = useDispatch();

	function dismiss() {
		dispatch( saveSettings( { update_notice: SearchRegexi10n.update_notice } ) );
		SearchRegexi10n.update_notice = false;
	}

	if ( !update_notice || !has_capability( CAP_SEARCHREGEX_OPTIONS ) ) {
		return null;
	}

	return (
		<Notice>
			<p>
				{ createInterpolateElement(
					sprintf( __( 'Version %s installed! Please read the {{url}}release notes{{/url}} for details.', 'search-regex' ), update_notice ),
					{
						url: <ExternalLink url={ 'https://searchregex.com/blog/searchregex-version-' + update_notice.replace( '.', '-' ) + '/' } />
					},
				) }
				&nbsp;
				<Button onClick={ dismiss }>{ __( 'OK', 'search-regex' ) }</Button>
			</p>
		</Notice>
	);
}

export default UpdateNotice;
