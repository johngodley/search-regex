/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */
import Dropdown from 'component/dropdown';
import './style.scss';

function DropdownMenu( { menu } ) {
	return (
		<Dropdown
			align="right"
			hasArrow
			renderToggle={ ( isOpen, toggle ) => (
				<button className="searchregex-dropdownmenu" onClick={ toggle }>
					{ isOpen && <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-hidden="true" focusable="false"><path d="M11 13h2v-2h-2v2zm-6 0h2v-2H5v2zm12-2v2h2v-2h-2z"></path></svg> }
					{ ! isOpen && <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-hidden="true" focusable="false"><path d="M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z"></path></svg> }
				</button>
			) }
			renderContent={ ( toggle ) => (
				<ul className="searchregex-dropdownmenu__menu" onClick={ toggle }>
					{ menu.map( ( item, key ) => <li key={ key }>{ item }</li> ) }
				</ul>
			) }
		/>
	)
}

export default DropdownMenu;
