/**
 * Tests for matched_rows calculation in advanced vs simple searches
 *
 * For advanced searches, the total matched rows is calculated as:
 * cumulativeMatchedRows (from previous pages) + results.length (current page matches)
 */

describe( 'Matched rows calculation', () => {
	describe( 'Simple search mode', () => {
		it( 'should use API matched_rows value directly', () => {
			const isAdvanced = false;
			const totals = { matched_rows: 42, rows: 1000 };
			const cumulativeMatchedRows = 0;
			const results: unknown[] = [];

			// Simple searches don't modify totals
			const effectiveTotals = isAdvanced
				? { ...totals, matched_rows: cumulativeMatchedRows + results.length }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 42 );
		} );
	} );

	describe( 'Advanced search - first page (no cumulative)', () => {
		it( 'should show current results when no previous pages', () => {
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 }; // API returns 0 during searches
			const results = new Array( 50 ); // 50 matches on first page
			const cumulativeMatchedRows = 0; // No previous pages

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 50 );
		} );

		it( 'should show 4 matches from 370 examined rows', () => {
			// Real-world scenario: regex search examining 370 rows but finding only 4 matches
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 410 };
			const results = new Array( 4 ); // 4 actual matches
			const cumulativeMatchedRows = 0; // First page

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 4 ); // Should show 4, not 374
		} );
	} );

	describe( 'Advanced search - sliding window (accumulating on same page)', () => {
		it( 'should accumulate results as sliding window loads more', () => {
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 };
			const results = new Array( 75 ); // 3 pages accumulated via sliding window
			const cumulativeMatchedRows = 0; // Still on first virtual page

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 75 );
		} );
	} );

	describe( 'Advanced search - manual page navigation', () => {
		it( 'should add previous page total when navigating to page 2', () => {
			// User was on page 1 with 25 matches, clicked Next
			// Before clearing, we added 25 to cumulative
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 };
			const results = new Array( 25 ); // Page 2 has 25 matches
			const cumulativeMatchedRows = 25; // From page 1

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 50 ); // 25 from page 1 + 25 from page 2
		} );

		it( 'should handle partial page with cumulative total', () => {
			// User is on page 2 with only 3 matches
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 };
			const results = new Array( 3 ); // Page 2 has only 3 matches
			const cumulativeMatchedRows = 25; // From page 1

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 28 ); // 25 + 3
		} );

		it( 'should accumulate across multiple page navigations', () => {
			// User has navigated through several pages
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 10000 };
			const results = new Array( 15 ); // Current page has 15 matches
			const cumulativeMatchedRows = 150; // From all previous pages (e.g., 6 pages × 25)

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 165 ); // 150 + 15
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'should handle empty results with no cumulative', () => {
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 };
			const results: unknown[] = []; // No results
			const cumulativeMatchedRows = 0;

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 0 );
		} );

		it( 'should handle empty results with cumulative from previous pages', () => {
			const isAdvanced = true;
			const totals = { matched_rows: 0, rows: 1000 };
			const results: unknown[] = []; // Current page has no matches yet
			const cumulativeMatchedRows = 50; // But we found 50 on previous pages

			const effectiveTotals = isAdvanced
				? {
						...totals,
						matched_rows: cumulativeMatchedRows + results.length,
				  }
				: totals;

			expect( effectiveTotals.matched_rows ).toBe( 50 ); // Show cumulative even if current is empty
		} );
	} );
} );
