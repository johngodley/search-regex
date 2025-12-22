import { create } from 'zustand';

interface MessageStore {
	errors: unknown[];
	notices: string[];
	addError: ( error: unknown ) => void;
	addNotice: ( notice: string ) => void;
	clearErrors: () => void;
	clearNotices: () => void;
}

export const useMessageStore = create< MessageStore >()( ( set ) => ( {
	errors: [],
	notices: [],

	addError: ( error ) => {
		set( ( state ) => ( { errors: [ ...state.errors, error ] } ) );
		// eslint-disable-next-line no-console -- This is a console error
		console.error( error );
	},

	addNotice: ( notice ) => {
		set( ( state ) => ( { notices: [ ...state.notices, notice ] } ) );
	},

	clearErrors: () => set( { errors: [] } ),

	clearNotices: () => set( { notices: [] } ),
} ) );
