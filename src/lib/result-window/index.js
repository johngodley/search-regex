import { useEffect, useState } from 'react';
import { useDelta } from 'react-delta';

export const INCREMENT_FAST = 1.2;
export const INCREMENT_SLOW = 0.8;

const REQUEST_MAX = 1000;

const LIMIT_MAX = 2000;
const LIMIT_MIN = 3;

const TIME_GAP = 500;
const TIME_DELAY = 500;

const DEFAULT_WINDOW_SIZE = 200;

let lastTime = 0;

/**
 * Scale up the per-page window depending on how many requests are made
 * @param {Integer} requestCount Request count
 * @param {Integer} perPage Standard per page
 */
function adjustPerPage( requestCount, perPage, speed = INCREMENT_FAST ) {
	if ( requestCount > LIMIT_MIN ) {
		return Math.min( LIMIT_MAX, Math.round( perPage * requestCount * speed ) );
	}

	return perPage;
}

/**
 * Don't flood the server with requests by ensuring that there's enough of a gap between this request and the last
 * @param {Function} cb Callback function
 */
function throttle( cb ) {
	const currentTime = new Date().getTime();

	setTimeout(
		() => {
			lastTime = currentTime;
			cb();
		},
		currentTime - lastTime > TIME_GAP ? 0 : TIME_DELAY
	);
}

export function useSlidingActionWindow( canLoad, requestCount, onPerform, onError ) {
	const [ windowPage, setWindowPage ] = useState( 0 );
	const deltaCount = useDelta( 0 );

	useEffect(() => {
		if ( requestCount === 0 || ! canLoad ) {
			return;
		}

		if ( requestCount > REQUEST_MAX ) {
			onError();
		}

		if ( deltaCount && deltaCount.prev && deltaCount.prev < deltaCount.curr ) {
			// Made a replace - scale down the window
			setWindowPage( Math.max( 0, windowPage - 5 ) );
		} else {
			// No replacements, scale up the window
			setWindowPage( windowPage + 1 );
		}

		throttle( () => onPerform( adjustPerPage( windowPage, DEFAULT_WINDOW_SIZE ) ) );
	}, [ requestCount ]);
}

export function useSlidingSearchWindow( canLoad, requestCount, perPage, onPerform, onError ) {
	// If the requestCount changes then consider making another request
	useEffect(() => {
		if ( requestCount === 0 || ! canLoad ) {
			return;
		}

		if ( requestCount > REQUEST_MAX ) {
			onError();
		}

		throttle( () => onPerform( adjustPerPage( requestCount, perPage ) ) );
	}, [ requestCount ]);
}
