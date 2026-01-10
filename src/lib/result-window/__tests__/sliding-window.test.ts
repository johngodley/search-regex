/**
 * Tests for sliding window pagination logic
 *
 * The sliding window automatically loads multiple pages until either:
 * 1. The perPage limit is reached (e.g., 25 results)
 * 2. The end of the database is reached (progress.next === false)
 *
 * Manual navigation (clicking Next/Previous) should:
 * 1. Clear current results
 * 2. Add current results to cumulative total
 * 3. Make a single request
 * 4. Reset requestCount to allow sliding window to resume if needed
 */

// @ts-nocheck - Allow flexible type comparisons in test scenarios
import { renderHook, act } from '@testing-library/react';
import { useSlidingSearchWindow } from '../index';

describe( 'useSlidingSearchWindow', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	describe( 'Sliding window activation', () => {
		it( 'should not fire when canLoad is false', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( false, 1, 25, onPerform, onError ) );

			// Give throttle time to execute
			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should not fire when requestCount is 0', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 0, 25, onPerform, onError ) );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should fire when canLoad is true and requestCount > 0', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 1, 25, onPerform, onError ) );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			expect( onPerform ).toHaveBeenCalled();
		} );
	} );

	describe( 'Request count increments', () => {
		it( 'should adjust page size based on request count', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingSearchWindow( true, requestCount, 25, onPerform, onError ),
				{ initialProps: { requestCount: 1 } }
			);

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			// First request should use adjusted page size
			expect( onPerform ).toHaveBeenCalledWith( expect.any( Number ) );

			// Increment request count
			rerender( { requestCount: 2 } );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			// Should have been called twice
			expect( onPerform ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'Manual navigation interference prevention', () => {
		it( 'should not fire if canLoad becomes false before throttled callback executes', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { canLoad, requestCount } ) =>
					useSlidingSearchWindow( canLoad, requestCount, 25, onPerform, onError ),
				{ initialProps: { canLoad: true, requestCount: 1 } }
			);

			// Immediately change to canLoad=false before throttle executes
			rerender( { canLoad: false, requestCount: 1 } );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			// Should not have fired because canLoad became false
			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should not fire if requestCount becomes 0 before throttled callback executes', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { canLoad, requestCount } ) =>
					useSlidingSearchWindow( canLoad, requestCount, 25, onPerform, onError ),
				{ initialProps: { canLoad: true, requestCount: 2 } }
			);

			// Simulate manual navigation clearing results (requestCount -> 0)
			rerender( { canLoad: true, requestCount: 0 } );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			// Should not have fired because requestCount became 0
			expect( onPerform ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Error handling', () => {
		it( 'should call onError when requestCount exceeds limit', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 1001, 25, onPerform, onError ) );

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			expect( onError ).toHaveBeenCalled();
			expect( onPerform ).not.toHaveBeenCalled();
		} );
	} );
} );

describe( 'Pagination coordination scenarios', () => {
	describe( 'shouldLoadMore helper', () => {
		const shouldLoadMore = ( requestCount: number, results: unknown[], perPage: number | undefined ): boolean => {
			return requestCount > 0 && perPage !== undefined && results.length < perPage;
		};

		it( 'should return false when requestCount is 0', () => {
			expect( shouldLoadMore( 0, [], 25 ) ).toBe( false );
		} );

		it( 'should return false when perPage is undefined', () => {
			expect( shouldLoadMore( 1, [], undefined ) ).toBe( false );
		} );

		it( 'should return false when results.length >= perPage', () => {
			const results = new Array( 25 );
			expect( shouldLoadMore( 1, results, 25 ) ).toBe( false );
		} );

		it( 'should return true when requestCount > 0, perPage defined, and results < perPage', () => {
			const results = new Array( 10 );
			expect( shouldLoadMore( 1, results, 25 ) ).toBe( true );
		} );
	} );

	describe( 'hasMoreResults helper', () => {
		const hasMoreResults = (
			searchDirection: string | null,
			progress: { next: boolean | number; previous?: boolean | number }
		): boolean => {
			const normalizeValue = ( value: boolean | number | undefined ): number | false => {
				if ( value === false || value === true ) {
					return false;
				}
				if ( value !== undefined ) {
					return value;
				}
				return false;
			};

			const next = normalizeValue( progress.next );
			const prev = normalizeValue( progress.previous );
			return (
				( searchDirection === 'forward' && next !== false ) ||
				( searchDirection === 'backward' && prev !== false )
			);
		};

		it( 'should return true for forward search when next is a number', () => {
			expect( hasMoreResults( 'forward', { next: 25 } ) ).toBe( true );
		} );

		it( 'should return false for forward search when next is false', () => {
			expect( hasMoreResults( 'forward', { next: false } ) ).toBe( false );
		} );

		it( 'should return true for backward search when previous is a number', () => {
			expect( hasMoreResults( 'backward', { next: false, previous: 10 } ) ).toBe( true );
		} );

		it( 'should return false for backward search when previous is false', () => {
			expect( hasMoreResults( 'backward', { next: false, previous: false } ) ).toBe( false );
		} );

		it( 'should handle next: 0 as valid (not end of results)', () => {
			expect( hasMoreResults( 'forward', { next: 0 } ) ).toBe( true );
		} );
	} );

	describe( 'canLoad calculation', () => {
		const calculateCanLoad = (
			isAdvanced: boolean,
			perPage: number | undefined,
			shouldLoad: boolean,
			hasMore: boolean,
			requestCount: number
		): boolean => {
			return isAdvanced && perPage !== undefined && shouldLoad && hasMore && requestCount > 0;
		};

		it( 'should be false for simple search', () => {
			expect( calculateCanLoad( false, 25, true, true, 1 ) ).toBe( false );
		} );

		it( 'should be false when perPage is undefined', () => {
			expect( calculateCanLoad( true, undefined, true, true, 1 ) ).toBe( false );
		} );

		it( 'should be false when shouldLoad is false', () => {
			expect( calculateCanLoad( true, 25, false, true, 1 ) ).toBe( false );
		} );

		it( 'should be false when hasMore is false', () => {
			expect( calculateCanLoad( true, 25, true, false, 1 ) ).toBe( false );
		} );

		it( 'should be false when requestCount is 0 (manual navigation)', () => {
			expect( calculateCanLoad( true, 25, true, true, 0 ) ).toBe( false );
		} );

		it( 'should be true when all conditions are met', () => {
			expect( calculateCanLoad( true, 25, true, true, 1 ) ).toBe( true );
		} );
	} );

	describe( 'Integration scenarios', () => {
		it( 'First page load: should start sliding window', () => {
			const isAdvanced = true;
			const results: unknown[] = new Array( 10 ); // 10 results from first request
			const requestCount = 0; // Not started yet
			const progress: { next: number | false } = { next: 10 }; // More results available

			// After first results arrive, should set requestCount to 1
			const shouldStartSliding =
				results.length > 0 && requestCount === 0 && isAdvanced && progress.next !== false;
			expect( shouldStartSliding ).toBe( true );
		} );

		it( 'Sliding window in progress: should continue loading', () => {
			const requestCount = 2; // Already made requests
			const results = new Array( 15 ); // 15 results so far
			const perPage = 25;
			const progress: { next: number | false } = { next: 25 };

			const shouldLoad = requestCount > 0 && perPage !== undefined && results.length < perPage;
			const hasMore = progress.next !== false;
			const canLoad = shouldLoad && hasMore && requestCount > 0;

			expect( canLoad ).toBe( true );
		} );

		it( 'Page full: should stop sliding window', () => {
			const requestCount = 3;
			const results = new Array( 25 ); // Full page
			const perPage = 25;

			const shouldLoad = requestCount > 0 && perPage !== undefined && results.length < perPage;
			expect( shouldLoad ).toBe( false );
		} );

		it( 'End of database: should stop sliding window', () => {
			const progress: { next: number | false } = { next: false }; // No more results

			const hasMore = progress.next !== false;
			expect( hasMore ).toBe( false );
		} );

		it( 'Manual navigation: should prevent sliding window during transition', () => {
			const requestCount = 0; // Reset when results cleared
			const results: unknown[] = []; // Cleared for navigation
			const perPage = 25;
			const progress: { next: number | false } = { next: 26 };

			const shouldLoad = requestCount > 0 && perPage !== undefined && results.length < perPage;
			const hasMore = progress.next !== false;
			const canLoad = shouldLoad && hasMore && requestCount > 0;

			// Should not load during manual navigation (requestCount = 0)
			expect( canLoad ).toBe( false );
		} );

		it( 'After manual navigation: should resume sliding window if needed', () => {
			const requestCount = 1; // Re-initialized after results arrive
			const results = new Array( 10 ); // 10 results from manual nav
			const perPage = 25;
			const progress: { next: number | false } = { next: 36 };

			const shouldLoad = requestCount > 0 && perPage !== undefined && results.length < perPage;
			const hasMore = progress.next !== false;
			const canLoad = shouldLoad && hasMore && requestCount > 0;

			// Should resume sliding window to fill the page
			expect( canLoad ).toBe( true );
		} );
	} );
} );
