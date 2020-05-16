/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */
import SimplePagination from './simple-pagination';
import AdvancedPagination from './advanced-pagination';
import './style.scss';

function Pagination( props ) {
	const { totals, searchDirection, advanced } = props;
	const { matched_rows, matched_phrases, rows } = totals;

	if ( matched_rows === null ) {
		return <div className="tablenav-pages"><div className="displaying-num">&nbsp;</div></div>;
	}

	if ( advanced ) {
		return <AdvancedPagination { ...props } total={ rows } searchDirection={ searchDirection } />;
	}

	return <SimplePagination { ...props } matchedRows={ matched_rows } matchedPhrases={ matched_phrases } total={ rows } />;
}

export default Pagination;
