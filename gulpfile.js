/* eslint-disable no-console */
const { src, dest, series } = require( 'gulp' );
const fs = require( 'fs' );
const crypto = require( 'crypto' );
const globby = require( 'globby' );
const path = require( 'path' );
const zip = require( 'gulp-zip' );
const sort = require( 'gulp-sort' );
const wpPot = require( 'gulp-wp-pot' );
const request = require( 'request' );
const po2json = require( 'gulp-po2json' );
const through = require( 'through2' );
const i18n_calypso = require( 'i18n-calypso-cli' );
const he = require( 'he' );
const download = require( 'download' );
const pkg = require( './package.json' );
const config = require( './.config.json' ); // Local config

const LOCALE_PERCENT_COMPLETE = 40;
const AVAILABLE_LANGUAGES_URL = 'https://translate.wordpress.org/api/projects/wp-plugins/search-regex/stable';
const LOCALE_URL = 'https://translate.wordpress.org/projects/wp-plugins/search-regex/stable/$LOCALE/default/export-translations?format=';
const SVN_SOURCE_FILES = [
	'./**',
	'!node_modules/**',
	'!node_modules',
	'!bin/**',
	'!bin',
	'!hooks/**',
	'!hooks',
	'!client/**',
	'!client',
	'!tests/**',
	'!tests',
	'!yarn.lock',
	'!package.json',
	'!gulpfile.js',
	'!postcss.config.js',
	'!README.md',
	'!phpunit.xml',
	'!webpack.config.js',
	'!package-lock.json',
	'!e2e/**',
	'!e2e',
	'!docker-compose.yml',
	'!.DS_Store',
	'!api-doc/**',
	'!api/*.md',
	'!vendor/**',
	'!vendor',
	'!phpcs.xml.dist',
	'!psalm.xml',
	'!composer.json',
	'!composer.lock',
];
const versionHeader = md5 => `<?php

define( 'SEARCHREGEX_VERSION', '${ pkg.version }' );
define( 'SEARCHREGEX_BUILD', '${ md5 }' );
define( 'SEARCHREGEX_MIN_WP', '${ pkg.wordpress.supported }' );
`;

function downloadLocale( locale, wpName, type ) {
	const url = LOCALE_URL.replace( '$LOCALE', locale ) + type;

	download( url, 'locale', {
		filename: 'search-regex-' + wpName + '.' + type,
	} )
		.catch( e => {
			console.error( 'Failed to download: ' + url, e );
		} );
}

const removeFromTarget = ( paths, rootPath ) => {
	paths
		.map( item => {
			const relative = path.resolve( '..', path.relative( path.join( rootPath, '..' ), item ) );

			if ( ! fs.existsSync( relative ) ) {
				return relative;
			}

			return false;
		} )
		.filter( item => item )
		.map( item => {
			const relative = path.join( rootPath, '..', path.relative( '..', item ) );

			/* eslint-disable no-console */
			console.log( 'Removed: ' + relative );
			fs.unlinkSync( relative );
		} );
};

const copyPlugin = ( target, cb ) => src( SVN_SOURCE_FILES )
	.pipe( dest( target ) )
	.on( 'end', () => {
		// Check which files are in the target but dont exist in the source
		globby( target + '**' )
			.then( paths => {
				removeFromTarget( paths, target );
			} );

		if ( cb ) {
			cb();
		}
	} );

function version( cb ) {
	fs.readFile( 'search-regex.js', ( error, data ) => {
		const md5 = crypto
			.createHash( 'md5' )
			.update( data, 'utf8' )
			.digest( 'hex' );

		fs.writeFile( 'search-regex-version.php', versionHeader( md5 ), cb );
	} );
}

const svn = () => copyPlugin( config.svn_target, () => console.log( 'SVN exported to ' + config.svn_target ) );

function plugin() {
	const zipTarget = path.resolve( config.export_target, '..' );
	const zipName = 'search-regex-' + pkg.version + '.zip';

	console.log( 'Exporting: ' + zipName + ' to ' + config.export_target );

	return copyPlugin( config.export_target, () => {
		return src( config.export_target + '/**', { base: path.join( config.export_target, '..' ) } )
			.pipe( zip( zipName ) )
			.pipe( dest( zipTarget ) );
	} );
}

function potJson( done ) {
	return src( [ 'locale/*.po' ] )
		.pipe( po2json() )
		.pipe( through.obj( ( file, enc, cb ) => {
			const json = JSON.parse( String( file.contents ) );
			const keys = Object.keys( json );

			for ( let x = 0; x < keys.length; x++ ) {
				const key = keys[ x ];
				const newObj = [];

				for ( let z = 1; z < json[ key ].length; z++ ) {
					newObj.push( json[ key ][ z ] );
				}

				json[ key ] = newObj;
			}

			file.contents = new Buffer( he.decode( JSON.stringify( json ) ) );
			cb( null, file );
		} ) )
		.pipe( dest( 'locale/json/' ) )
		.on( 'end', function() {
			done();
		} );
}

function potDownload( cb ) {
	request( AVAILABLE_LANGUAGES_URL, ( error, response, body ) => {
		if ( error || response.statusCode !== 200 ) {
			console.error( 'Failed to download available languages from ' + AVAILABLE_LANGUAGES_URL, error );
			return;
		}

		const json = JSON.parse( body );

		for ( let x = 0; x < json.translation_sets.length; x++ ) {
			const locale = json.translation_sets[ x ];

			if ( parseInt( locale.percent_translated, 10 ) > LOCALE_PERCENT_COMPLETE ) {
				console.log( 'Downloading ' + locale.locale );

				downloadLocale( locale.locale, locale.wp_locale, 'po' );
				downloadLocale( locale.locale, locale.wp_locale, 'mo' );
			}
		}

		cb();
	} );
}

function potExtract( cb ) {
	globby( [ 'client/**/*.js', '!client/lib/polyfill/index.js' ] )
		.then( files => {
			let result = i18n_calypso( {
				projectName: 'Search Regex',
				inputPaths: files,
				phpArrayName: 'search_regex_strings',
				format: 'PHP',
				textdomain: 'search-regex',
				keywords: [ 'translate', '__' ],
			} );

			// There's a bug where it doesnt escape $ correctly - do it here
			result = result.replace( /\$(.*?)\$/g, '%%$1%%' );
			result = result.replace( /%%/g, '\\$' );
			result = result.replace( /\\\\/g, '\\' );

			fs.writeFileSync( 'search-regex-strings.php', result, 'utf8' );
			cb();
		} );
}

function potGenerate() {
	const pot = {
		domain: 'search-regex',
		destFile: 'search-regex.pot',
		package: 'Search Regex',
		bugReport: 'https://wordpress.org/plugins/search-regex/',
	};

	return src( [ '**/*.php' ] )
		.pipe( sort() )
		.pipe( wpPot( pot ) )
		.pipe( dest( 'locale/search-regex.pot' ) );
}

exports.svn = svn;
exports.version = version;
exports.plugin = plugin;
exports.pot = series( potDownload, potExtract, potGenerate, potJson );
