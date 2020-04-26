const OFFSET_MAX = 20;
const OFFSET_ARROW = 5;

export const PORTAL = 'react-portal';

function adjustForAlignment( leftPos, popoverWidth, toggleWidth, align ) {
	if ( align === 'right' ) {
		return leftPos + toggleWidth - popoverWidth;
	}

	if ( align === 'centre' ) {
		return leftPos - ( ( popoverWidth + toggleWidth ) / 2 );
	}

	return leftPos;
}

// This is left aligned by default. Adjust to fit the alignment and screen size
export function getAdjustedPosition( position, togglePosition, align, ref, hasArrow ) {
	if ( ! ref.current ) {
		return {
			...position,
			visibility: 'hidden',  // Hide until ready otherwise we get a flicker
		};
	}

	const { width } = ref.current.getBoundingClientRect();
	const minLeftPos = togglePosition.parentWidth - width - OFFSET_MAX;
	const adjustedLeft = adjustForAlignment( togglePosition.left, position.width ? position.width : width, togglePosition.width, align );

	return {
		...position,
		left: Math.min( minLeftPos, adjustedLeft ),
		top: hasArrow ? position.top + OFFSET_ARROW : position.top,
	};
}

export function getDimensions( ref, offset ) {
	if ( ref === null ) {
		return {};
	}

	const parentRect = document.getElementById( PORTAL ).getBoundingClientRect();
	const { height, width, left, top } = ref.getBoundingClientRect();

	return {
		left: left - parentRect.left + ( offset ? offset : 0 ),
		top: top - height,
		width,
		height,
		parentWidth: parentRect.width,
		parentHeight: parentRect.height,
	};
}

export function getPosition( togglePosition, matchToggle ) {
	if ( togglePosition === null ) {
		return {};
	}

	const { left, top, width, height } = togglePosition;
	const position = {
		left: left,
		top: ( top + height ),
	};

	if ( matchToggle ) {
		position.width = width;
	}

	return position;
}

export function adjustArrowStyle( style, ref ) {
	if ( ref ) {
		return {
			...style,
			width: ref.getBoundingClientRect().width,
		};
	}

	return style;
}
