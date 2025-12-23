import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink, Notice, Button, createInterpolateElement } from '@wp-plugin-components';
import { useSaveSettings } from '../../hooks/use-settings';
/* eslint-disable camelcase */
import { CAP_SEARCHREGEX_OPTIONS, has_capability } from '../../lib/capabilities';
/* eslint-enable camelcase */

function UpdateNotice() {
	/* eslint-disable camelcase */
	const { update_notice = false } = SearchRegexi10n;
	/* eslint-enable camelcase */
	const saveSettingsMutation = useSaveSettings();

	function dismiss() {
		/* eslint-disable camelcase */
		const updateNotice = SearchRegexi10n.update_notice;
		if ( updateNotice !== undefined ) {
			saveSettingsMutation.mutate( { update_notice: updateNotice } );
		}
		SearchRegexi10n.update_notice = false;
		/* eslint-enable camelcase */
	}

	/* eslint-disable camelcase */
	if ( ! update_notice || ! has_capability( CAP_SEARCHREGEX_OPTIONS ) ) {
		/* eslint-enable camelcase */
		return null;
	}

	return (
		<Notice>
			<p>
				{
					/* eslint-disable camelcase */
					createInterpolateElement(
						sprintf(
							/* translators: %s: version installed */
							__(
								'Version %s installed! Please read the {{url}}release notes{{/url}} for details.',
								'search-regex'
							),
							update_notice
						),
						{
							url: (
								<ExternalLink
									url={
										'https://searchregex.com/blog/searchregex-version-' +
										update_notice.replace( '.', '-' ) +
										'/'
									}
								/>
							),
						}
					)
					/* eslint-enable camelcase */
				}
				&nbsp;
				<Button onClick={ dismiss }>{ __( 'OK', 'search-regex' ) }</Button>
			</p>
		</Notice>
	);
}

export default UpdateNotice;
