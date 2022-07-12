/**
 * External dependencies
 */

import classnames from 'classnames';
import { connect } from 'react-redux';
import { __, sprintf, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import Preset from './preset';
import { exportPresets, uploadPreset, importClipboard, setClipboard, clearPresetError } from '../../state/preset/action';
import './style.scss';
import { Uploader, Placeholder, Spinner, Error, ExternalLink } from '@wp-plugin-components';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from '../../state/settings/type';

function PresetDebug( { debug } ) {
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
				</a>{' '}
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

function PresetManagement( props ) {
	const {
		presets,
		onExport,
		clipboardStatus,
		uploadStatus,
		onUploadFile,
		onImportClipboard,
		clipboard,
		onSetClipboard,
		isUploading,
		onClearError,
		error,
		errorContext,
		imported,
	} = props;

	return (
		<>
			<table
				className={ classnames(
					'wp-list-table',
					'widefat',
					'fixed',
					'striped',
					'items',
					'searchregex-presets'
				) }
			>
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
				<ExternalLink url="https://searchregex.com/preset/">{ __( 'Download presets!', 'search-regex' ) }</ExternalLink>
			</p>

			<div className="searchregex-presetactions">
				{ presets.length > 0 && (
					<button className="button button-secondary" onClick={ onExport }>
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
					isUploading={ isUploading }
					isUploaded={ uploadStatus === STATUS_COMPLETE }
					disabled={ clipboardStatus === STATUS_IN_PROGRESS || uploadStatus === STATUS_IN_PROGRESS }
					renderUnselected={ () => (
						<>
							<h3>{ __( 'Import a JSON file', 'search-regex' ) }</h3>
							<p>{ __( "Click 'Add File' or drag and drop here." ) }</p>
						</>
					) }
					renderSelected={ ( file ) => (
						<>
							<h3>{ __( 'File selected', 'search-regex' ) }</h3>
							<p>
								<code>{ file.name }</code>
							</p>
						</>
					) }
					renderUploading={ ( file ) => (
						<>
							<h3>{ __( 'Importing', 'search-regex' ) }</h3>

							<p>
								<code>{ file.name }</code>
							</p>

							<Placeholder />
						</>
					) }
					renderUploaded={ ( cancel ) => (
						<>
							<h3>
								{ sprintf( _n( 'Uploaded %(total)d preset', 'Uploaded %(total)d presets', imported, 'search-regex' ), {
									total: imported,
								} ) }
							</h3>

							<button className="button-secondary" onClick={ cancel }>
								{ __( 'Done', 'search-regex' ) }
							</button>
						</>
					) }
					onUpload={ onUploadFile }
				/>

				<h4>{ __( 'Import preset from clipboard', 'search-regex' ) }</h4>

				{ clipboardStatus === STATUS_FAILED && (
					<Error
						mini
						errors={ [ error ] }
						title={ __( 'Unable to import preset', 'search-regex' ) }
						type="error"
						onClear={ onClearError }
						context={ errorContext }
						renderDebug={ PresetDebug }
						versions={ SearchRegexi10n.versions }
					>
						{ __(
							'Please check your JSON data is a valid preset. You may have copied it incorrectly, or pasted something that is not a preset.'
						) }
					</Error>
				) }

				<textarea
					placeholder={ __( 'Paste preset JSON.', 'search-regex' ) }
					rows={ 3 }
					value={ clipboard }
					onChange={ ( ev ) => onSetClipboard( ev.target.value ) }
					disabled={ uploadStatus === STATUS_IN_PROGRESS }
				/>
				<p>
					<button
						disabled={ uploadStatus === STATUS_IN_PROGRESS || clipboard.length === 0 }
						className="button button-secondary"
						onClick={ () => onImportClipboard( clipboard ) }
					>
						{ __( 'Import', 'search-regex' ) }
					</button>
				</p>
				{ uploadStatus === STATUS_IN_PROGRESS && clipboard && <Spinner /> }
			</div>
		</>
	);
}

function mapDispatchToProps( dispatch ) {
	return {
		onExport: () => {
			dispatch( exportPresets() );
		},
		onUploadFile: ( file ) => {
			dispatch( uploadPreset( file ) );
		},
		onImportClipboard: ( clipboard ) => {
			dispatch( importClipboard( clipboard ) );
		},
		onSetClipboard: ( clipboard ) => {
			dispatch( setClipboard( clipboard ) );
		},
		onClearError: () => {
			dispatch( clearPresetError() );
		},
	};
}

function mapStateToProps( state ) {
	const { presets, clipboardStatus, uploadStatus, clipboard, isUploading, error, errorContext, imported } = state.preset;
	const { sources } = state.search;

	return {
		error,
		errorContext,
		presets,
		sources,
		clipboardStatus,
		uploadStatus,
		clipboard,
		isUploading,
		imported,
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( PresetManagement );
