/**
 * External dependencies
 */
import configureMockStore from 'redux-mock-store'
import thunk from 'redux-thunk'
import fetchMock from 'fetch-mock'

/**
 * Internal dependencies
 */

import * as actions from 'state/search/action';
import {
	SEARCH_REPLACE_ALL,
	SEARCH_REPLACE_ALL_COMPLETE,
	SEARCH_REPLACE_ALL_MORE,
	SEARCH_REPLACE_ROW,
	SEARCH_REPLACE_COMPLETE,
	SEARCH_START_FRESH,
	SEARCH_START_MORE,
	SEARCH_COMPLETE,
} from 'state/search/type';

const middlewares = [ thunk ];
const mockStore = configureMockStore( middlewares );

const getInitialSearchStore = ( search ) => ( { search: { search } } );
const getRequest = ( url, body ) => ( { url, body } );
const getResponse = ( body ) => ( { body } );

function testAction( initialStore, request, response, action, expectedActions ) {
	const store = mockStore( initialStore );

	// Mock the API request
	fetchMock.post( request, response );

	// Test the action
	return store.dispatch( action ).then( () => {
		expect( store.getActions() ).toEqual( expectedActions );
	} );
}

describe( 'search actions', () => {
	afterEach( () => {
		fetchMock.restore();
	} );

	describe( 'replaceAll', () => {
		function testReplace( replaceRequest, replaceResponse, action, actionType, offset ) {
			const initialStore = getInitialSearchStore( {
				source: [ 'post' ],
				searchPhrase: 'cat',
				replacement: replaceRequest,
				searchFlags: {},
				sourceFlags: {},
			} );
			const results = { rows: 1, phrases: 2 };
			const expectedActions = [
				{ type: actionType },
				{ type: SEARCH_REPLACE_ALL_COMPLETE, results }
			];
			const request = getRequest( '/wp-json/search-regex/v1/replace/?_wpnonce=nonce', {
				source: [ 'post' ],
				searchPhrase: 'cat',
				replacement: replaceResponse,
				searchFlags: [],
				sourceFlags: [],
				replacePhrase: replaceResponse,
				perPage: 25,
				offset,
			} );
			const response = getResponse( { results } );

			return testAction( initialStore, request, response, action(), expectedActions );
		}

		test( 'a replaceAll calls APi with correct parameters and returns action', () => {
			return testReplace( 'cat', 'cat', () => actions.replaceAll( 25 ), SEARCH_REPLACE_ALL, '0' );
		} );

		test( 'a delete replaceAll calls APi with correct parameters and returns action', () => {
			return testReplace( null, '', () => actions.replaceAll( 25 ), SEARCH_REPLACE_ALL, '0' );
		} );

		test( 'a replaceNext calls APi with correct parameters and returns action', () => {
			return testReplace( 'cat', 'cat', () => actions.replaceNext( 1, 25 ), SEARCH_REPLACE_ALL_MORE, 1 );
		} );

		test( 'a delete replaceNext calls APi with correct parameters and returns action', () => {
			return testReplace( null, '', () => actions.replaceNext( 1, 25 ), SEARCH_REPLACE_ALL_MORE, 1 );
		} );
	} );

	describe( 'replaceRow', () => {
		function testReplace( replaceRequest, replacement, extra, columnId = null, posId = null ) {
			const initialStore = getInitialSearchStore( {
				source: [ 'post' ],
				searchPhrase: 'cat',
				replacement: replaceRequest,
				searchFlags: {},
				sourceFlags: {},
			} );
			const results = { rows: 1, phrases: 2 };
			const expectedActions = [
				{ type: SEARCH_REPLACE_ROW, rowId: 1 },
				{ type: SEARCH_REPLACE_COMPLETE, results, rowId: 1 }
			];
			const request = getRequest( '/wp-json/search-regex/v1/source/post/1/replace/?_wpnonce=nonce', {
				searchPhrase: 'cat',
				replacement,
				searchFlags: [],
				sourceFlags: [],
				replacePhrase: replacement,
				...extra,
			} );
			const response = getResponse( { results } );
			const action = actions.replaceRow( replacement, 'post', 1, columnId, posId );

			return testAction( initialStore, request, response, action, expectedActions );
		}

		test( 'replace an entire row', () => {
			return testReplace( 'cat', 'cat' );
		} );

		test( 'replace a column in a row', () => {
			return testReplace( 'cat', 'cat', { columnId: 'post_content' }, 'post_content' );
		} );

		test( 'replace a specific position within a column in a row', () => {
			return testReplace( 'cat', 'cat', { columnId: 'post_content', posId: 5 }, 'post_content', 5 );
		} );
	} );

	describe( 'search', () => {
		function testSearch( action, actionType, extra ) {
			const initialStore = getInitialSearchStore( {
				source: [ 'post' ],
				searchPhrase: 'cat',
				searchFlags: {},
				sourceFlags: {},
				replacement: '',
				perPage: 25,
			} );
			const requestBody = {
				source: [ 'post' ],
				searchPhrase: 'cat',
				searchFlags: [],
				sourceFlags: [],
				replacement: '',
				page: 0,
				searchDirection: 'forward',
				perPage: 25,
				...extra,
			};
			const results = [ 1 ];
			const expectedActions = [
				{ type: actionType, ...requestBody },
				{ type: SEARCH_COMPLETE, results, perPage: 25 }
			];
			const request = getRequest( '/wp-json/search-regex/v1/search/?_wpnonce=nonce', requestBody );
			const response = getResponse( { results: [ 1 ] } );

			return testAction( initialStore, request, response, action(), expectedActions );
		}

		test( 'search calls API with correct parameters', () => {
			return testSearch( () => actions.search( 0 ), SEARCH_START_FRESH );
		} );

		test( 'searchMore calls API with correct parameters', () => {
			return testSearch( () => actions.searchMore( 0, 25, 5 ), SEARCH_START_MORE, { limit: 5, perPage: 25 } );
		} );
	} );
} );
