import { __ } from '@wordpress/i18n';
import SimplePagination from './simple-pagination';
import AdvancedPagination from './advanced-pagination';
import './style.scss';

interface Progress {
	next?: number | false;
	prev?: number | false;
	previous?: number | false;
	current?: number;
}

interface Totals {
	matched_rows: number | null; // eslint-disable-line camelcase
	rows: number;
}

interface PaginationProps {
	totals: Totals;
	searchDirection: string;
	advanced: boolean;
	resultsDirty: boolean;
	progress: Progress;
	isLoading?: boolean;
	perPage?: number;
	noTotal?: boolean;
}

function Pagination( props: PaginationProps ) {
	const {
		totals,
		searchDirection,
		advanced,
		resultsDirty,
		progress,
		isLoading = false,
		perPage = 25,
		noTotal = false,
	} = props;
	const { matched_rows: matchedRows, rows } = totals; // eslint-disable-line camelcase

	// For advanced searches, show pagination if we have totals data (even if matched_rows is 0)
	// Only hide if matchedRows is null/undefined (no search performed yet)
	// For simple searches, hide if there are no matches AND no pagination buttons
	const shouldHide = advanced
		? matchedRows === null || matchedRows === undefined
		: ( matchedRows === null || matchedRows === undefined || matchedRows === 0 ) &&
		  ! progress.next &&
		  ! progress.prev;

	if ( shouldHide ) {
		return (
			<div className="tablenav-pages">
				<div className="displaying-num">&nbsp;</div>
			</div>
		);
	}

	if ( resultsDirty ) {
		return (
			<p className="searchregex-resultsdirty">
				{ __(
					'Your search conditions have changed. Please refresh to see the latest results.',
					'search-regex'
				) }
			</p>
		);
	}

	if ( advanced ) {
		return (
			<AdvancedPagination
				total={ rows }
				progress={ progress as { previous?: number | false; next?: number | false } }
				isLoading={ isLoading }
				searchDirection={ searchDirection }
				noTotal={ noTotal }
				totals={ totals as { matched_rows: number } }
			/>
		);
	}

	return (
		<SimplePagination
			progress={ progress as { current: number; previous: number | false; next: number | false } }
			isLoading={ isLoading }
			matchedRows={ matchedRows as number }
			perPage={ perPage }
			noTotal={ noTotal }
			total={ rows }
		/>
	);
}

export default Pagination;
