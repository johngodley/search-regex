import { SEARCH_FAIL, SEARCH_CANCEL, SEARCH_VALUES, SEARCH_LABEL } from '../type';
import type { SearchValues } from '../../../types/search';

export * from './search';
export * from './row';

export const setSearch = ( searchValue: Partial< SearchValues > ) => ( { type: SEARCH_VALUES, searchValue } );
export const setError = ( error: string ) => ( { type: SEARCH_FAIL, error: { message: error } } );
export const cancel = () => ( { type: SEARCH_CANCEL, clearAll: false } );
export const clear = () => ( { type: SEARCH_CANCEL, clearAll: true } );
export const setLabel = ( labelId: string, labelValue: string ) => ( { type: SEARCH_LABEL, labelId, labelValue } );
