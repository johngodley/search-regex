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
	const { totals, searchDirection } = props;
	const { matched_rows, matched_phrases, rows } = totals;

	if ( matched_rows === null ) {
		return <div className="tablenav-pages"><div className="displaying-num">&nbsp;</div></div>;
	}

	if ( matched_rows > 0 ) {
		return <SimplePagination { ...props } matchedRows={ matched_rows } matchedPhrases={ matched_phrases } total={ rows } />;
	}

	return <AdvancedPagination { ...props } total={ rows } searchDirection={ searchDirection } />;
}

export default Pagination;
