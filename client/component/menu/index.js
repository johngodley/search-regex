/**
 * External dependencies
 */

import React from 'react';
import { translate as __ } from 'lib/locale';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import MenuItem from './menu-item';
import { has_page_access } from 'lib/capabilities';
import './style.scss';

const getMenu = () => [
	{
		name: __( 'Search & Replace' ),
		value: '',
	},
	{
		name: __( 'Options' ),
		value: 'options',
	},
	{
		name: __( 'Support' ),
		value: 'support',
	},
];

const isCurrent = ( page, item ) => page === item.value || page === 'search' && item.value === '';

const Menu = props => {
	const { onChangePage, current } = props;
	const menu = getMenu().filter( option => has_page_access( option.value ) || option.value === '' && has_page_access( 'search' ) );

	if ( menu.length < 2 ) {
		return null;
	}

	return (
		<div className="subsubsub-container">
			<ul className="subsubsub">
				{
					menu
						.map( ( item, pos ) => <MenuItem key={ pos } item={ item } isCurrent={ isCurrent( current, item ) } onClick={ onChangePage } /> )
						.reduce( ( prev, curr ) => [ prev, ' | ', curr ] )
				}
			</ul>
		</div>
	);
};

Menu.propTypes = {
	onChangePage: PropTypes.func.isRequired,
};

export default Menu;
