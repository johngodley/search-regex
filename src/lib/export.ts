// @ts-ignore - file-saver doesn't have types
import { saveAs } from 'file-saver';

function getDataFormat( results: unknown[], format: string ): string {
	if ( format === 'json' ) {
		return JSON.stringify( results );
	}

	return results.join( '\n' );
}

function getDataFilename( format: string ): string {
	if ( format === 'json' ) {
		return 'export.json';
	}

	if ( format === 'csv' ) {
		return 'export.csv';
	}

	if ( format === 'sql' ) {
		return 'export.sql';
	}

	return 'export.txt';
}

export function saveExport( results: unknown[], format: string ): void {
	const data = getDataFormat( results, format );
	const filename = getDataFilename( format );

	saveAs( new Blob( [ data ] ), filename );
}
