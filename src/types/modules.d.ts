/**
 * Type declarations for third-party modules without type definitions
 */

declare module 'qs' {
	export interface IStringifyOptions {
		arrayFormat?: 'indices' | 'brackets' | 'repeat' | 'comma';
		indices?: boolean;
		[ key: string ]: unknown;
	}

	export interface IParseOptions {
		[ key: string ]: unknown;
	}

	export function stringify( obj: Record< string, unknown >, options?: IStringifyOptions ): string;
	export function parse( str: string, options?: IParseOptions ): Record< string, unknown >;
}

declare module 'deep-equal' {
	function deepEqual( a: unknown, b: unknown ): boolean;
	export = deepEqual;
}

declare module 'react-copy-to-clipboard' {
	import { ReactNode } from 'react';

	export interface CopyToClipboardProps {
		text: string;
		onCopy?: ( text: string, result: boolean ) => void;
		children: ReactNode;
	}

	export class CopyToClipboard extends React.Component< CopyToClipboardProps > {}
}
