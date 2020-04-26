/**
 * External dependencies
 */

import React from 'react';
import classnames from 'classnames';

export default function PopoverArrows( { style, align } ) {
	const classes = classnames( 'redirect-popover__arrows', {
		'redirect-popover__arrows__left': align === 'left',
		'redirect-popover__arrows__right': align === 'right',
		'redirect-popover__arrows__centre': align === 'centre',
	} );

	return (
		<div className={ classes } style={ style } />
	);
}
