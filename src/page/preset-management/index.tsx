import clsx from 'clsx';
import { __, sprintf, _n } from '@wordpress/i18n';
import { ChangeEvent, useState } from 'react';
import { z } from 'zod';
import Preset from './preset';
import { usePresets, useUploadPreset, useExportPresets } from '../../hooks/use-presets';
import { presetImportSchema } from '../../lib/api-schemas';
import './style.scss';
import { Uploader, Placeholder, Spinner, Error as ErrorComponent, ExternalLink } from '@wp-plugin-components';

function PresetDebug( debug: string ) {
	return (
		<>
			<p>
				<a
					href={
						'mailto:john@searchregex.com?subject=Search%20Regex%20Error&body=' + encodeURIComponent( debug )
					}
					className="button-secondary"
				>
					{ __( 'Create An Issue', 'search-regex' ) }
				</a>{ ' ' }
				<a
					href={
						'https://github.com/johngodley/search-regex/issues/new?title=Search%20Regex%20Error&body=' +
						encodeURIComponent( debug )
					}
					className="button-secondary"
				>
					{ __( 'Email', 'search-regex' ) }
				</a>
			</p>
		</>
	);
}

function PresetManagement() {
	const { data: presets = [] } = usePresets();
	const uploadMutation = useUploadPreset();
	const { exportPresets } = useExportPresets();
	const [ clipboardText, setClipboardText ] = useState( '' );
	const [ clipboardError, setClipboardError ] = useState< { error: unknown; context: string } | null >( null );

	const handleUploadFile = ( file: File ) => {
		uploadMutation.mutate( file );
	};

	const handleImportClipboard = ( text: string ) => {
		try {
			// Parse JSON first
			const json = JSON.parse( text );

			// ✨ Validate preset structure with Zod
			const validationResult = presetImportSchema.safeParse( json );

			if ( ! validationResult.success ) {
				// Zod validation failed - provide detailed error
				const errorMessage = validationResult.error.issues
					.map( ( err: z.ZodIssue ) => {
						const path = err.path.join( '.' );
						return `${ path ? `${ path }: ` : '' }${ err.message }`;
					} )
					.join( '; ' );

				setClipboardError( {
					error: new globalThis.Error( `Invalid preset structure: ${ errorMessage }` ),
					context: text,
				} );
				return;
			}

			// Validation passed - use validated data
			const validatedPresets = validationResult.data;
			const blob = new Blob( [ JSON.stringify( validatedPresets ) ], { type: 'application/json' } );
			const file = new File( [ blob ], 'preset.json', { type: 'application/json' } );

			setClipboardError( null );
			uploadMutation.mutate( file );
		} catch ( error ) {
			// JSON parse error or other error
			if ( error instanceof SyntaxError ) {
				setClipboardError( {
					error: new globalThis.Error( `Invalid JSON: ${ error.message }` ),
					context: text,
				} );
			} else if ( error instanceof z.ZodError ) {
				// This shouldn't happen since we use safeParse, but handle it anyway
				const errorMessage = error.issues
					.map( ( err: z.ZodIssue ) => {
						const path = err.path.join( '.' );
						return `${ path ? `${ path }: ` : '' }${ err.message }`;
					} )
					.join( '; ' );
				setClipboardError( {
					error: new globalThis.Error( `Invalid preset structure: ${ errorMessage }` ),
					context: text,
				} );
			} else {
				setClipboardError( {
					error: error instanceof globalThis.Error ? error : new globalThis.Error( String( error ) ),
					context: text,
				} );
			}
		}
	};

	const handleClearError = () => {
		setClipboardError( null );
		uploadMutation.reset();
	};

	const isFileUploading = uploadMutation.isPending && ! clipboardText;
	const isClipboardUploading = uploadMutation.isPending && clipboardText.length > 0;
	const uploadComplete = uploadMutation.isSuccess;
	const uploadFailed = clipboardError !== null;

	return (
		<>
			<table className={ clsx( 'wp-list-table', 'widefat', 'fixed', 'striped', 'items', 'searchregex-presets' ) }>
				<thead>
					<tr>
						<th className="searchregex-preset__name">{ __( 'Name', 'search-regex' ) }</th>
						<th className="searchregex-preset__search">{ __( 'Search', 'search-regex' ) }</th>
						<th className="searchregex-preset__flags">{ __( 'Flags', 'search-regex' ) }</th>
					</tr>
				</thead>

				<tbody>
					{ presets.map( ( preset ) => (
						<Preset preset={ preset } key={ preset.id } />
					) ) }

					{ presets.length === 0 && (
						<tr>
							<td colSpan={ 3 }>{ __( 'There are no presets', 'search-regex' ) }</td>
						</tr>
					) }
				</tbody>
			</table>

			<p>
				<ExternalLink url="https://searchregex.com/preset/">
					{ __( 'Download presets!', 'search-regex' ) }
				</ExternalLink>
			</p>

			<div className="searchregex-presetactions">
				{ presets.length > 0 && (
					<button className="button button-secondary" onClick={ exportPresets }>
						{ __( 'Export JSON', 'search-regex' ) }
					</button>
				) }
			</div>

			<h3>{ __( 'Import JSON', 'search-regex' ) }</h3>

			<div className="searchregex-presetimport">
				<Uploader
					addFileText={ __( 'Add file', 'search-regex' ) }
					uploadText={ __( 'Upload', 'search-regex' ) }
					cancelText={ __( 'Cancel', 'search-regex' ) }
					isUploading={ isFileUploading }
					isUploaded={ uploadComplete }
					disabled={ isClipboardUploading || isFileUploading }
					renderUnselected={ () => (
						<>
							<h3>{ __( 'Import a JSON file', 'search-regex' ) }</h3>
							<p>{ __( "Click 'Add File' or drag and drop here.", 'search-regex' ) }</p>
						</>
					) }
					renderSelected={ ( file: File ) => (
						<>
							<h3>{ __( 'File selected', 'search-regex' ) }</h3>
							<p>
								<code>{ file.name }</code>
							</p>
						</>
					) }
					renderUploading={ ( file: File ) => (
						<>
							<h3>{ __( 'Importing', 'search-regex' ) }</h3>

							<p>
								<code>{ file.name }</code>
							</p>

							<Placeholder />
						</>
					) }
					renderUploaded={ ( cancel: () => void ) => (
						<>
							<h3>
								{ sprintf(
									/* translators: %(total)d: number of presets uploaded */
									_n(
										'Uploaded %(total)d preset',
										'Uploaded %(total)d presets',
										uploadMutation.data?.imported ?? 0,
										'search-regex'
									),
									{
										total: uploadMutation.data?.imported ?? 0,
									}
								) }
							</h3>
							<button className="button-secondary" onClick={ cancel }>
								{ __( 'Done', 'search-regex' ) }
							</button>
						</>
					) }
					onUpload={ handleUploadFile }
				/>

				<h4>{ __( 'Import preset from clipboard', 'search-regex' ) }</h4>

				{ uploadFailed && (
					<ErrorComponent
						mini
						errors={ clipboardError?.error ? [ clipboardError.error ] : [] }
						title={ __( 'Unable to import preset', 'search-regex' ) }
						type="error"
						onClear={ handleClearError }
						context={ clipboardError?.context ?? null }
						renderDebug={ PresetDebug }
						versions={ SearchRegexi10n.versions }
						locale={ SearchRegexi10n.locale }
					>
						{ __(
							'Please check your JSON data is a valid preset. You may have copied it incorrectly, or pasted something that is not a preset.',
							'search-regex'
						) }
					</ErrorComponent>
				) }

				<textarea
					placeholder={ __( 'Paste preset JSON.', 'search-regex' ) }
					rows={ 3 }
					value={ clipboardText }
					onChange={ ( ev: ChangeEvent< HTMLTextAreaElement > ) => setClipboardText( ev.target.value ) }
					disabled={ isFileUploading }
				/>
				<p>
					<button
						disabled={ isFileUploading || clipboardText.length === 0 }
						className="button button-secondary"
						onClick={ () => handleImportClipboard( clipboardText ) }
					>
						{ __( 'Import', 'search-regex' ) }
					</button>
				</p>
				{ isClipboardUploading && <Spinner /> }
			</div>
		</>
	);
}

export default PresetManagement;
