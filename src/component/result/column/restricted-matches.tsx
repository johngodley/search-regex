import { __ } from '@wordpress/i18n';

function RestrictedMatches(): JSX.Element {
	return (
		<div className="searchregex-result__more">
			{ __(
				'Maximum number of matches exceeded and hidden from view. These will be included in any replacements.',
				'search-regex'
			) }
		</div>
	);
}

export default RestrictedMatches;
