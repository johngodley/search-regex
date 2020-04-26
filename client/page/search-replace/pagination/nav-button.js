/**
 * External dependencies
 */

import React from 'react';

const Nav = props => {
	const { title, button, className, enabled, onClick } = props;
	const click = ( ev ) => {
		ev.preventDefault();
		onClick();
	};

	if ( enabled ) {
		return (
			<a className={ className + ' button' } href="#" onClick={ click }>
				<span className="screen-reader-text">{ title }</span>
				<span aria-hidden="true">{ button }</span>
			</a>
		);
	}

	return (
		<span className="tablenav-pages-navspan button disabled" aria-hidden="true">{ button }</span>
	);
};

export default Nav;
