import Options from '../options';
import Support from '../support';
import PresetManagement from '../preset-management';
import SearchReplace from '../search-replace';

interface PageContentProps {
	page: string;
}

function PageContent( { page }: PageContentProps ) {
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
