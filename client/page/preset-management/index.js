/**
 * External dependencies
 */

import React from 'react';
import classnames from 'classnames';
import { connect } from 'react-redux';
import { translate as __ } from 'wp-plugin-lib/locale';

/**
 * Internal dependencies
 */

import Preset from './preset';
import { exportPresets, uploadPreset, importClipboard, setClipboard, clearPresetError } from 'state/preset/action';
import './style.scss';
import { Uploader, Placeholder, Spinner, Error } from 'wp-plugin-components';
import { STATUS_IN_PROGRESS, STATUS_COMPLETE, STATUS_FAILED } from 'state/settings/type';

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
					{ __( 'Create An Issue' ) }
				</a>{' '}
				<a
					href={
						'https://github.com/johngodley/search-regex/issues/new?title=Search%20Regex%20Error&body=' +
						encodeURIComponent( debug )
					}
					className="button-secondary"
				>
					{ __( 'Email' ) }
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
						<th className="searchregex-preset__name">{ __( 'Name' ) }</th>
						<th className="searchregex-preset__search">{ __( 'Search' ) }</th>
						<th className="searchregex-preset__flags">{ __( 'Flags' ) }</th>
					</tr>
				</thead>

				<tbody>
					{ presets.map( ( preset ) => (
						<Preset preset={ preset } key={ preset.id } />
					) ) }

					{ presets.length === 0 && (
						<tr>
							<td colSpan={ 3 }>{ __( 'There are no presets' ) }</td>
						</tr>
					) }
				</tbody>
			</table>

			<div className="searchregex-presetactions">
				{ presets.length > 0 && (
					<button className="button button-secondary" onClick={ onExport }>
						{ __( 'Export JSON' ) }
					</button>
				) }
			</div>

			<h3>{ __( 'Import JSON' ) }</h3>

			<div className="searchregex-presetimport">
				<Uploader
					isUploading={ isUploading }
					isUploaded={ uploadStatus === STATUS_COMPLETE }
					disabled={ clipboardStatus === STATUS_IN_PROGRESS || uploadStatus === STATUS_IN_PROGRESS }
					renderUnselected={ () => (
						<>
							<h3>{ __( 'Import a JSON file' ) }</h3>
							<p>{ __( "Click 'Add File' or drag and drop here." ) }</p>
						</>
					) }
					renderSelected={ ( file ) => (
						<>
							<h3>{ __( 'File selected' ) }</h3>
							<p>
								<code>{ file.name }</code>
							</p>
						</>
					) }
					renderUploading={ ( file ) => (
						<>
							<h3>{ __( 'Importing' ) }</h3>

							<p>
								<code>{ file.name }</code>
							</p>

							<Placeholder />
						</>
					) }
					renderUploaded={ ( cancel ) => (
						<>
							<h3>
								{ __( 'Uploaded %(total)d preset', 'Uploaded %(total)d presets', {
									count: imported,
									args: {
										total: imported,
									},
								} ) }
							</h3>

							<button className="button-secondary" onClick={ cancel }>
								{ __( 'Done' ) }
							</button>
						</>
					) }
					onUpload={ onUploadFile }
				/>

				<h4>{ __( 'Import preset from clipboard' ) }</h4>

				{ clipboardStatus === STATUS_FAILED && (
					<Error
						mini
						errors={ [ error ] }
						title={ __( 'Unable to import preset' ) }
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
					placeholder={ __( 'Paste a single preset JSON.' ) }
					rows={ 3 }
					value={ clipboard }
					onChange={ ( ev ) => onSetClipboard( ev.target.value ) }
					disabled={ uploadStatus === STATUS_IN_PROGRESS }
				/>
				<p>
					<button
						disabled={ uploadStatus === STATUS_IN_PROGRESS }
						className="button button-secondary"
						onClick={ () => onImportClipboard( clipboard ) }
					>
						{ __( 'Import' ) }
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
