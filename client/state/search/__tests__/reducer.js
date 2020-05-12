/**
 * Internal dependencies
 */

import reducer from 'state/search/reducer';
import { getInitialSearch } from 'state/search/initial';
import {
	SEARCH_VALUES,
	SEARCH_START_FRESH,
	SEARCH_FAIL,
	SEARCH_COMPLETE,
	SEARCH_REPLACE_ALL,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_REPLACE_ALL_COMPLETE,
	SEARCH_DELETE_COMPLETE,
	SEARCH_CANCEL,
	SEARCH_BACKWARD,
	SEARCH_FORWARD,
	SEARCH_LOAD_ROW_COMPLETE,
	SEARCH_SAVE_ROW_COMPLETE,
	SEARCH_START_MORE,
} from 'state/search/type';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';

const DEFAULT_STATE = getInitialSearch();

const getState = ( state = {}, search = {} ) => ( {
	...DEFAULT_STATE,
	...state,
	search: {
		...DEFAULT_STATE.search,
		...search,
	},
} );

describe( 'search value reducer', () => {
	test( 'unknown action returns same state', () => {
		const original = getState();
		const expected = getState();
		const action = { type: 'something' };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'search form change returns new search details and clears results', () => {
		const original = getState( { results: [ 1 ], status: 'cat' } );
		const expected = getState( { status: null, results: [] }, { searchPhrase: 'hello' } );
		const action = { type: SEARCH_VALUES, searchValue: { searchPhrase: 'hello' } };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'replacement change returns new search details and does not clear results', () => {
		const original = getState( { results: [ 1 ], status: 'cat' } );
		const expected = getState( { results: [ 1 ], status: 'cat' }, { replacement: 'hello' } );
		const action = { type: SEARCH_VALUES, searchValue: { replacement: 'hello' } };

		expect( reducer( original, action ) ).toEqual( expected );
	} );
} );

describe( 'search cancel reducer', () => {
	test( 'cancel action resets status', () => {
		const original = getState( { results: [ 5 ], requestCount: 5, replaceCount: 5, phraseCount: 5, status: 'cat' } );
		const expected = getState( { results: [ 5 ], requestCount: 0, replaceCount: 0, phraseCount: 0, status: STATUS_COMPLETE } );
		const action = { type: SEARCH_CANCEL };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'cancel clears all data', () => {
		const original = getState( { results: [ 5 ], requestCount: 5, replaceCount: 5, phraseCount: 5, status: 'cat' } );
		const expected = getState( { results: [], requestCount: 0, replaceCount: 0, phraseCount: 0, status: STATUS_COMPLETE } );
		const action = { type: SEARCH_CANCEL, clearAll: true };

		expect( reducer( original, action ) ).toEqual( expected );
	} );
} );

describe( 'search reducer', () => {
	test( 'starting a search on first page resets totals', () => {
		const original = getState( { requestCount: 5, results: [ 5 ], progress: { thing: 1 }, totals: { thing: 2 } } );
		const expected = getState( { requestCount: 0, status: STATUS_IN_PROGRESS, totals: {}, progress: {}, results: [], searchDirection: SEARCH_FORWARD, showLoading: true } );
		const action = { type: SEARCH_START_FRESH, page: 0, searchDirection: SEARCH_FORWARD };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'starting a search on other pages doesnt reset totals', () => {
		const original = getState( { requestCount: 5, results: [ 5 ], progress: { thing: 1 }, totals: { thing: 2 } } );
		const expected = getState( { requestCount: 0, status: STATUS_IN_PROGRESS, progress: { thing: 1 }, totals: { thing: 2 }, results: [], searchDirection: SEARCH_FORWARD, showLoading: true } );
		const action = { type: SEARCH_START_FRESH, page: 1, searchDirection: SEARCH_FORWARD };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'continue searching when it has been cancelled changes nothing', () => {
		const original = getState( { status: STATUS_COMPLETE, canCancel: false, showLoading: false } );
		const expected = original;
		const action = { type: SEARCH_START_MORE };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'continue searching when it hasnt been cancelled', () => {
		const original = getState( { status: STATUS_IN_PROGRESS, canCancel: false, showLoading: false } );
		const expected = getState( { status: STATUS_IN_PROGRESS, canCancel: true, showLoading: true } );
		const action = { type: SEARCH_START_MORE };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'complete searching when it has been cancelled changes nothing', () => {
		const original = getState( { status: STATUS_COMPLETE, canCancel: false, showLoading: false } );
		const expected = original;
		const action = { type: SEARCH_COMPLETE };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	test( 'complete simple searching when it hasnt been cancelled resets state', () => {
		const original = getState( { status: STATUS_IN_PROGRESS, canCancel: true, showLoading: true } );
		const expected = getState( { status: STATUS_COMPLETE, canCancel: false, showLoading: false, results: [ 1 ], progress: 1, totals: { rows: 1 } } );
		const action = { type: SEARCH_COMPLETE, results: [ 1 ], progress: 1, totals: { rows: 1 } };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	// XXX
	test( 'complete advanced searching when it hasnt been cancelled resets state', () => {
		const original = getState( { status: STATUS_IN_PROGRESS, canCancel: false, showLoading: false }, { searchFlags: { regex: true } } );
		const expected = getState( { status: STATUS_COMPLETE, canCancel: false, showLoading: false, results: [ 1 ], progress: 1, totals: { rows: 1 } } );
		const action = { type: SEARCH_COMPLETE, results: [ 1 ], progress: 1, totals: { rows: 1 } };

		expect( reducer( original, action ) ).toEqual( expected );
	} );

	// XXX SEARCH_COMPLETE and not cancelled
} );
