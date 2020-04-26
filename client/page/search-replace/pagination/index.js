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
	const { matches, rows, searchDirection } = props;

	if ( matches === null ) {
		return <div className="tablenav-pages"><div className="displaying-num">&nbsp;</div></div>;
	}

	if ( matches > 0 ) {
		return <SimplePagination { ...props } matched={ matches } total={ rows } />;
	}

	return <AdvancedPagination { ...props } total={ rows } searchDirection={ searchDirection } />;
}

export default Pagination;
