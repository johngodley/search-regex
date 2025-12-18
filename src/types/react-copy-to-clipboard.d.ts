declare module 'react-copy-to-clipboard' {
	import { Component, ReactNode } from 'react';

	interface CopyToClipboardProps {
		text: string;
		children: ReactNode;
		onCopy?: ( text: string, result: boolean ) => void;
		options?: {
			debug?: boolean;
			message?: string;
			format?: string;
		};
	}

	export class CopyToClipboard extends Component< CopyToClipboardProps > {}
}
