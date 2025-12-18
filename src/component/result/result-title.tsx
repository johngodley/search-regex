import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wp-plugin-components';

interface ResultTitleProps {
	view?: string;
	title?: string;
}

function ResultTitle( { view, title }: ResultTitleProps ): JSX.Element | string {
	const alwaysTitle = title ? title : __( 'No title', 'search-regex' );

	if ( view ) {
		return <ExternalLink url={ view }>{ alwaysTitle }</ExternalLink>;
	}

	return alwaysTitle;
}

export default ResultTitle;
