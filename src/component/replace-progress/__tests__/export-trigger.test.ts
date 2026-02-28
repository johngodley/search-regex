/**
 * Tests for the export-trigger logic in ReplaceProgress.
 *
 * The two invariants under test:
 * 1. saveExport fires exactly once when the export completes (not on re-renders
 *    caused by unrelated state changes like actionOption edits).
 * 2. saveExport fires again on a subsequent export run after exportData is
 *    cleared and re-accumulated — i.e. the ref guard resets correctly.
 */

import { renderHook, act } from '@testing-library/react';
import { useEffect, useRef, useState } from 'react';
import { STATUS_COMPLETE, STATUS_IN_PROGRESS } from '../../../lib/constants';

// Mock saveExport so we can assert call counts without touching file-saver
const mockSaveExport = jest.fn();
jest.mock( '../../../lib/export', () => ( {
	saveExport: ( ...args: unknown[] ) => mockSaveExport( ...args ),
} ) );

// Import after mock registration
import { saveExport } from '../../../lib/export';

/**
 * Minimal reproduction of the export-trigger useEffect from ReplaceProgress.
 * Accepts the same inputs that drive the real effect so we can test the
 * ref-guard and reset behaviour in isolation.
 */
function useExportTrigger( {
	status,
	nextIsFalse,
	action,
	exportData,
	format,
}: {
	status: string | null;
	nextIsFalse: boolean;
	action: string;
	exportData: unknown[];
	format: string;
} ) {
	const exportSavedRef = useRef( false );

	useEffect( () => {
		if ( exportData.length === 0 ) {
			exportSavedRef.current = false;
			return;
		}

		if (
			status === STATUS_COMPLETE &&
			nextIsFalse &&
			action === 'export' &&
			! exportSavedRef.current
		) {
			exportSavedRef.current = true;
			saveExport( exportData, format );
		}
	}, [ status, nextIsFalse, action, exportData, format ] );
}

describe( 'ReplaceProgress export trigger', () => {
	beforeEach( () => {
		mockSaveExport.mockClear();
	} );

	describe( 'single export run', () => {
		it( 'fires saveExport exactly once when export completes', () => {
			const exportData = [ { id: 1 } ];

			const { rerender } = renderHook(
				( props ) => useExportTrigger( props ),
				{
					initialProps: {
						status: STATUS_IN_PROGRESS,
						nextIsFalse: false,
						action: 'export',
						exportData,
						format: 'json',
					},
				}
			);

			expect( mockSaveExport ).not.toHaveBeenCalled();

			act( () => {
				rerender( {
					status: STATUS_COMPLETE,
					nextIsFalse: true,
					action: 'export',
					exportData,
					format: 'json',
				} );
			} );

			expect( mockSaveExport ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveExport ).toHaveBeenCalledWith( exportData, 'json' );
		} );

		it( 'does not re-fire when format changes after completion', () => {
			const exportData = [ { id: 1 } ];
			const completedProps = {
				status: STATUS_COMPLETE,
				nextIsFalse: true,
				action: 'export',
				exportData,
				format: 'json',
			};

			const { rerender } = renderHook(
				( props ) => useExportTrigger( props ),
				{ initialProps: completedProps }
			);

			expect( mockSaveExport ).toHaveBeenCalledTimes( 1 );
			mockSaveExport.mockClear();

			// User changes format dropdown after completion — must NOT trigger another download
			act( () => {
				rerender( { ...completedProps, format: 'csv' } );
			} );

			expect( mockSaveExport ).not.toHaveBeenCalled();
		} );

		it( 'does not fire when action is not export', () => {
			act( () => {
				renderHook( () =>
					useExportTrigger( {
						status: STATUS_COMPLETE,
						nextIsFalse: true,
						action: 'replace',
						exportData: [ { id: 1 } ],
						format: 'json',
					} )
				);
			} );

			expect( mockSaveExport ).not.toHaveBeenCalled();
		} );

		it( 'does not fire when exportData is empty', () => {
			act( () => {
				renderHook( () =>
					useExportTrigger( {
						status: STATUS_COMPLETE,
						nextIsFalse: true,
						action: 'export',
						exportData: [],
						format: 'json',
					} )
				);
			} );

			expect( mockSaveExport ).not.toHaveBeenCalled();
		} );

		it( 'does not fire when next is not false (still paginating)', () => {
			act( () => {
				renderHook( () =>
					useExportTrigger( {
						status: STATUS_IN_PROGRESS,
						nextIsFalse: false,
						action: 'export',
						exportData: [ { id: 1 } ],
						format: 'json',
					} )
				);
			} );

			expect( mockSaveExport ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'multiple export runs in the same mounted session', () => {
		it( 'fires saveExport again after exportData is cleared and a new run completes', () => {
			const exportData = [ { id: 1 } ];

			const { rerender } = renderHook(
				( props ) => useExportTrigger( props ),
				{
					initialProps: {
						status: STATUS_IN_PROGRESS,
						nextIsFalse: false,
						action: 'export',
						exportData,
						format: 'json',
					},
				}
			);

			// First run completes
			act( () => {
				rerender( {
					status: STATUS_COMPLETE,
					nextIsFalse: true,
					action: 'export',
					exportData,
					format: 'json',
				} );
			} );

			expect( mockSaveExport ).toHaveBeenCalledTimes( 1 );
			mockSaveExport.mockClear();

			// User clicks Export again: clearExportData() fires, status goes to IN_PROGRESS
			act( () => {
				rerender( {
					status: STATUS_IN_PROGRESS,
					nextIsFalse: false,
					action: 'export',
					exportData: [], // clearExportData() resets the ref guard
					format: 'json',
				} );
			} );

			expect( mockSaveExport ).not.toHaveBeenCalled();

			// New export data accumulates and completes
			const exportData2 = [ { id: 2 }, { id: 3 } ];

			act( () => {
				rerender( {
					status: STATUS_COMPLETE,
					nextIsFalse: true,
					action: 'export',
					exportData: exportData2,
					format: 'json',
				} );
			} );

			expect( mockSaveExport ).toHaveBeenCalledTimes( 1 );
			expect( mockSaveExport ).toHaveBeenCalledWith( exportData2, 'json' );
		} );

		it( 'fires saveExport for each run across three consecutive exports', () => {
			const { rerender } = renderHook(
				( props ) => useExportTrigger( props ),
				{
					initialProps: {
						status: STATUS_IN_PROGRESS,
						nextIsFalse: false,
						action: 'export',
						exportData: [],
						format: 'json',
					},
				}
			);

			for ( let run = 1; run <= 3; run++ ) {
				const exportData = [ { id: run } ];

				// Data arrives and export completes
				act( () => {
					rerender( {
						status: STATUS_COMPLETE,
						nextIsFalse: true,
						action: 'export',
						exportData,
						format: 'json',
					} );
				} );

				expect( mockSaveExport ).toHaveBeenCalledTimes( 1 );
				expect( mockSaveExport ).toHaveBeenCalledWith( exportData, 'json' );
				mockSaveExport.mockClear();

				// Reset for next run
				act( () => {
					rerender( {
						status: STATUS_IN_PROGRESS,
						nextIsFalse: false,
						action: 'export',
						exportData: [],
						format: 'json',
					} );
				} );
			}
		} );
	} );
} );
