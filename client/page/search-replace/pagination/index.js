/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import SimplePagination from './simple-pagination';
import AdvancedPagination from './advanced-pagination';
import './style.scss';

function Pagination( props ) {
	const { totals, searchDirection, advanced, resultsDirty, progress } = props;
	const { matched_rows, matched_phrases, rows } = totals;

	if ( ( matched_rows === null || matched_rows === undefined || matched_rows === 0 ) && ! progress.next && ! progress.prev ) {
		return <div className="tablenav-pages"><div className="displaying-num">&nbsp;</div></div>;
	}

	if ( resultsDirty ) {
		return <p className="searchregex-resultsdirty">{ __( 'Your search conditions have changed. Please refresh to see the latest results.' ) }</p>
	}

	if ( advanced ) {
		return <AdvancedPagination { ...props } total={ rows } searchDirection={ searchDirection } />;
	}

	return <SimplePagination { ...props } matchedRows={ matched_rows } matchedPhrases={ matched_phrases } total={ rows } />;
}

export default Pagination;
