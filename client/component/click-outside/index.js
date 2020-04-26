/**
 * External dependencies
 */

import React, { useRef, useEffect } from 'react';

function isOutside( ev, containerRef ) {
	if ( ! containerRef.current ) {
		return false;
	}

	if ( containerRef.current.contains( ev.target ) ) {
		return false;
	}

	return true;
}

export default function ClickOutside( props ) {
	const containerRef = useRef( null );
	const { children, onOutside, className } = props;
	const outside = ( ev ) => {
		if ( isOutside( ev, containerRef ) || ev.key === 'Escape' ) {
			onOutside( ev );
		}
	};

	useEffect( () => {
		addEventListener( 'click', outside );
		addEventListener( 'keydown', outside );

		return () => {
			removeEventListener( 'click', outside );
			removeEventListener( 'keydown', outside );
		};
	}, [] );

	return (
		<div className={ className } ref={ containerRef }>
			{ children }
		</div>
	);
}
