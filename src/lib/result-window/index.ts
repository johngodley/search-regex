import { useEffect, useRef } from 'react';
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
 * @param requestCount
 * @param perPage
 * @param speed
 */
function adjustPerPage( requestCount: number, perPage: number, speed = INCREMENT_FAST ): number {
	if ( requestCount > LIMIT_MIN ) {
		return Math.min( LIMIT_MAX, Math.round( perPage * requestCount * speed ) );
	}

	return perPage;
}

/**
 * Don't flood the server with requests by ensuring that there's enough of a gap between this request and the last
 * @param cb
 */
function throttle( cb: () => void ): void {
	const currentTime = new Date().getTime();

	setTimeout(
		() => {
			lastTime = currentTime;
			cb();
		},
		currentTime - lastTime > TIME_GAP ? 0 : TIME_DELAY
	);
}

export function useSlidingActionWindow(
	canLoad: boolean,
	requestCount: number,
	onPerform: ( perPage: number ) => void,
	onError: () => void
): void {
	const windowPage = useRef( 0 );
	const deltaCount = useDelta( 0 );

	useEffect( () => {
		if ( requestCount === 0 || ! canLoad ) {
			return;
		}

		if ( requestCount > REQUEST_MAX ) {
			onError();
		}

		const previousPage = windowPage.current;
		const nextPage =
			deltaCount && deltaCount.prev && deltaCount.prev < deltaCount.curr
				? Math.max( 0, previousPage - 5 )
				: previousPage + 1;

		windowPage.current = nextPage;
		throttle( () => onPerform( adjustPerPage( nextPage, DEFAULT_WINDOW_SIZE ) ) );
	}, [ canLoad, deltaCount, onError, onPerform, requestCount ] );
}

export function useSlidingSearchWindow(
	canLoad: boolean,
	requestCount: number,
	perPage: number,
	onPerform: ( perPage: number ) => void,
	onError: () => void
): void {
	// Use refs to store the latest callbacks without causing re-renders
	const onPerformRef = useRef( onPerform );
	const onErrorRef = useRef( onError );
	// Use refs to store latest values to check inside throttled callback
	const canLoadRef = useRef( canLoad );
	const requestCountRef = useRef( requestCount );

	// Update refs when callbacks change
	useEffect( () => {
		onPerformRef.current = onPerform;
		onErrorRef.current = onError;
	}, [ onPerform, onError ] );

	// Update refs when values change
	useEffect( () => {
		canLoadRef.current = canLoad;
		requestCountRef.current = requestCount;
	}, [ canLoad, requestCount ] );

	useEffect( () => {
		if ( requestCount === 0 || ! canLoad ) {
			return;
		}

		if ( requestCount > REQUEST_MAX ) {
			onErrorRef.current();
			return;
		}

		const adjustedPerPage = adjustPerPage( requestCount, perPage );
		throttle( () => {
			// Check fresh values inside throttled callback to avoid stale closures
			if ( requestCountRef.current === 0 || ! canLoadRef.current ) {
				return;
			}
			onPerformRef.current( adjustedPerPage );
		} );
	}, [ canLoad, perPage, requestCount ] ); // Removed onError and onPerform from dependencies
}
