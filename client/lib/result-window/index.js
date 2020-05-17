export const INCREMENT_FAST = 1.2;
export const INCREMENT_SLOW = 0.8;

const LIMIT_MAX = 2000;
const LIMIT_MIN = 3;

const TIME_GAP = 500;
const TIME_DELAY = 300;

let lastTime = 0;

/**
 * Scale up the per-page window depending on how many requests are made
 * @param {Integer} requestCount Request count
 * @param {Integer} perPage Standard per page
 */
export function adjustPerPage( requestCount, perPage, speed = INCREMENT_FAST ) {
	if ( requestCount > LIMIT_MIN ) {
		return Math.min( LIMIT_MAX, Math.round( perPage * requestCount * speed ) );
	}

	return perPage;
}

/**
 * Don't flood the server with requests by ensuring that there's enough of a gap between this request and the last
 * @param {Function} cb Callback function
 */
export function throttle( cb ) {
	const currentTime = new Date().getTime();

	setTimeout( () => {
		lastTime = currentTime;
		cb();
	}, currentTime - lastTime > TIME_GAP ? 0 : TIME_DELAY );
}
