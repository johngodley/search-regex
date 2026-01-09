import { renderHook, act } from '@testing-library/react';
import { useSlidingSearchWindow, useSlidingActionWindow } from '../index';

// Mock react-delta
jest.mock( 'react-delta', () => ( {
	useDelta: jest.fn( ( value ) => ( {
		prev: value - 1,
		curr: value,
	} ) ),
} ) );

describe( 'result-window', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.runOnlyPendingTimers();
		jest.useRealTimers();
	} );

	describe( 'adjustPerPage helper (via useSlidingSearchWindow)', () => {
		it( 'should return the same perPage when requestCount is <= 3', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { canLoad, requestCount, perPage } ) =>
					useSlidingSearchWindow( canLoad, requestCount, perPage, onPerform, onError ),
				{
					initialProps: { canLoad: true, requestCount: 1, perPage: 100 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			// For requestCount=1, adjustPerPage should return perPage * 1 * INCREMENT_FAST = 100 * 1 * 1.2 = 120
			// But requestCount=1 is <= LIMIT_MIN (3), so it should return perPage unchanged
			expect( onPerform ).toHaveBeenCalledWith( 100 );

			onPerform.mockClear();

			// Test with requestCount=3
			rerender( { canLoad: true, requestCount: 3, perPage: 100 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledWith( 100 );
		} );

		it( 'should scale up perPage when requestCount > 3', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 4, 100, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			// For requestCount=4, adjustPerPage = Math.min(2000, Math.round(100 * 4 * 1.2)) = Math.min(2000, 480) = 480
			expect( onPerform ).toHaveBeenCalledWith( 480 );
		} );

		it( 'should cap perPage at LIMIT_MAX (2000)', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 20, 200, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			// For requestCount=20, adjustPerPage = Math.min(2000, Math.round(200 * 20 * 1.2)) = Math.min(2000, 4800) = 2000
			expect( onPerform ).toHaveBeenCalledWith( 2000 );
		} );
	} );

	describe( 'throttle helper', () => {
		it( 'should execute callback with throttling', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 1, 100, onPerform, onError ) );

			// Throttle uses setTimeout, so we need to flush all pending timers
			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should delay subsequent calls by TIME_DELAY (500ms)', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingSearchWindow( true, requestCount, 100, onPerform, onError ),
				{
					initialProps: { requestCount: 1 },
				}
			);

			// First call
			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			onPerform.mockClear();

			// Second call immediately after - should be delayed
			rerender( { requestCount: 2 } );

			act( () => {
				jest.advanceTimersByTime( 100 );
			} );

			expect( onPerform ).not.toHaveBeenCalled();

			act( () => {
				jest.advanceTimersByTime( 500 );
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'useSlidingSearchWindow', () => {
		it( 'should not call onPerform when requestCount is 0', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 0, 100, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should not call onPerform when canLoad is false', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( false, 1, 100, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should call onPerform when canLoad is true and requestCount > 0', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 1, 100, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			expect( onPerform ).toHaveBeenCalledWith( 100 );
		} );

		it( 'should call onError when requestCount exceeds REQUEST_MAX (1000)', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingSearchWindow( true, 1001, 100, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onError ).toHaveBeenCalledTimes( 1 );
			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should update when requestCount changes', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingSearchWindow( true, requestCount, 100, onPerform, onError ),
				{
					initialProps: { requestCount: 1 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			onPerform.mockClear();

			rerender( { requestCount: 2 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should use refs for callbacks to avoid dependency issues', () => {
			const onPerform1 = jest.fn();
			const onPerform2 = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { onPerform } ) => useSlidingSearchWindow( true, 1, 100, onPerform, onError ),
				{
					initialProps: { onPerform: onPerform1 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform1 ).toHaveBeenCalledTimes( 1 );

			// Change the callback - should use new callback without re-triggering effect
			rerender( { onPerform: onPerform2 } );

			// No additional calls should happen just from changing callback
			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform2 ).not.toHaveBeenCalled();
		} );

		it( 'should handle perPage changes', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { perPage } ) => useSlidingSearchWindow( true, 1, perPage, onPerform, onError ),
				{
					initialProps: { perPage: 100 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledWith( 100 );
			onPerform.mockClear();

			rerender( { perPage: 200 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledWith( 200 );
		} );
	} );

	describe( 'useSlidingActionWindow', () => {
		it( 'should not call onPerform when requestCount is 0', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingActionWindow( true, 0, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should not call onPerform when canLoad is false', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingActionWindow( false, 1, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should call onPerform with adjusted page size', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingActionWindow( true, 1, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			// For requestCount=1 (which is nextPage), adjustPerPage(1, 200) = 200 (since 1 <= LIMIT_MIN)
			expect( onPerform ).toHaveBeenCalledWith( 200 );
		} );

		it( 'should call onError when requestCount exceeds REQUEST_MAX', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			renderHook( () => useSlidingActionWindow( true, 1001, onPerform, onError ) );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onError ).toHaveBeenCalledTimes( 1 );
			// Note: useSlidingActionWindow still calls onPerform even after onError
			// This is different from useSlidingSearchWindow which returns early
			expect( onPerform ).toHaveBeenCalled();
		} );

		it( 'should increment page on each request', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingActionWindow( true, requestCount, onPerform, onError ),
				{
					initialProps: { requestCount: 1 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			// First call: nextPage = 0 + 1 = 1, adjustPerPage(1, 200) = 200
			expect( onPerform ).toHaveBeenCalledWith( 200 );

			onPerform.mockClear();
			rerender( { requestCount: 2 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			// Second call: nextPage = 1 + 1 = 2, adjustPerPage(2, 200) = 200
			expect( onPerform ).toHaveBeenCalledWith( 200 );
		} );
	} );

	describe( 'Integration scenarios', () => {
		it( 'should handle sequential pagination requests', async () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingSearchWindow( true, requestCount, 25, onPerform, onError ),
				{
					initialProps: { requestCount: 0 },
				}
			);

			// Start pagination
			rerender( { requestCount: 1 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			expect( onPerform ).toHaveBeenCalledWith( 25 );

			onPerform.mockClear();

			// Second request
			rerender( { requestCount: 2 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			expect( onPerform ).toHaveBeenCalledWith( 25 );

			onPerform.mockClear();

			// Third request
			rerender( { requestCount: 3 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			expect( onPerform ).toHaveBeenCalledWith( 25 );

			onPerform.mockClear();

			// Fourth request - should start scaling
			rerender( { requestCount: 4 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			// adjustPerPage(4, 25) = Math.min(2000, Math.round(25 * 4 * 1.2)) = Math.min(2000, 120) = 120
			expect( onPerform ).toHaveBeenCalledWith( 120 );
		} );

		it( 'should stop pagination when canLoad becomes false', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { canLoad, requestCount } ) =>
					useSlidingSearchWindow( canLoad, requestCount, 25, onPerform, onError ),
				{
					initialProps: { canLoad: true, requestCount: 1 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			onPerform.mockClear();

			// Stop loading
			rerender( { canLoad: false, requestCount: 2 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();
		} );

		it( 'should reset and start new pagination cycle', () => {
			const onPerform = jest.fn();
			const onError = jest.fn();

			const { rerender } = renderHook(
				( { requestCount } ) => useSlidingSearchWindow( true, requestCount, 25, onPerform, onError ),
				{
					initialProps: { requestCount: 1 },
				}
			);

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
			onPerform.mockClear();

			// Reset to 0
			rerender( { requestCount: 0 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).not.toHaveBeenCalled();

			// Start new cycle
			rerender( { requestCount: 1 } );

			act( () => {
				jest.runAllTimers();
			} );

			expect( onPerform ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
