/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { useDispatch } from 'react-redux'

/**
 * Internal dependencies
 */
import { ExternalLink, Notice, Button } from '@wp-plugin-components';
import { saveSettings } from '../../state/settings/action';
import { CAP_SEARCHREGEX_OPTIONS, has_capability } from '../../lib/capabilities';

function UpdateNotice() {
	const { update_notice = false } = SearchRegexi10n;
	const dispatch = useDispatch();

	function dismiss() {
		dispatch( saveSettings( { update_notice: SearchRegexi10n.update_notice } ) );
		SearchRegexi10n.update_notice = false;
	}

	if ( ! update_notice || ! has_capability( CAP_SEARCHREGEX_OPTIONS ) ) {
		return null;
	}

	return (
		<Notice>
			<p>
				{ __( 'Version %s installed! Please read the {{url}}release notes{{/url}} for details.', {
					args: update_notice,
					components: {
						url: (
							<ExternalLink
								url={ 'https://searchregex.com/blog/redirection-version-' + update_notice.replace( '.', '-' ) + '/' }
							/>
						),
					},
				} ) }
				&nbsp;
				<Button onClick={ dismiss }>{ __( 'OK' ) }</Button>
			</p>
		</Notice>
	);
}

export default UpdateNotice;
