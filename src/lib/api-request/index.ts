import { postApiRequest, getApiRequest, uploadApiRequest } from '@wp-plugin-lib';
import type { PresetValue } from '../../types/preset';
import type { SearchValues } from '../../types/search';
type ApiRequest = ReturnType< typeof getApiRequest >;

type ApiParams = Record< string, unknown >;

const SearchRegexApi = {
	setting: {
		get: (): ApiRequest => getApiRequest( 'search-regex/v1/setting' ),
		update: ( settings: ApiParams ): ApiRequest => postApiRequest( 'search-regex/v1/setting', settings ),
	},
	search: {
		perform: ( data: ApiParams ): ApiRequest => postApiRequest( 'search-regex/v1/search', data ),
	},
	preset: {
		save: ( search: SearchValues, name: string ): ApiRequest =>
			postApiRequest( 'search-regex/v1/preset', { ...search, name } ),
		update: ( preset: PresetValue ): ApiRequest =>
			postApiRequest( `search-regex/v1/preset/id/${ preset.id }`, preset as unknown as ApiParams ),
		delete: ( id: string ): ApiRequest => postApiRequest( `search-regex/v1/preset/id/${ id }/delete` ),
		export: (): ApiRequest => getApiRequest( 'search-regex/v1/preset', { force: true } ),
		upload: ( file: File | Blob ): ApiRequest => uploadApiRequest( 'search-regex/v1/preset/import', {}, file ),
	},
	source: {
		deleteRow: ( source: string, rowId: number | string ): ApiRequest =>
			postApiRequest( `search-regex/v1/source/${ source }/row/${ rowId }/delete` ),
		loadRow: ( source: string, rowId: number | string ): ApiRequest =>
			getApiRequest( `search-regex/v1/source/${ source }/row/${ rowId }` ),
		saveRow: ( source: string, rowId: number | string, replacement: string, search: SearchValues ): ApiRequest =>
			postApiRequest( `search-regex/v1/source/${ source }/row/${ rowId }`, { ...search, replacement } ),
		complete: ( source: string, column: string, value: string ): ApiRequest =>
			getApiRequest( `search-regex/v1/source/${ source }/complete/${ column }`, { value } ),
	},
	plugin: {
		checkApi: ( url: string, post = false ): ApiRequest => {
			const request = post
				? postApiRequest( 'search-regex/v1/plugin/test', { test: 'ping' } )
				: getApiRequest( 'search-regex/v1/plugin/test' );

			request.url = url.substr( 0, 4 ) === 'http' ? url + request.url : request.url;

			return request;
		},
	},
};

export default SearchRegexApi;
