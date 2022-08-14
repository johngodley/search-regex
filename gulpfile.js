/* eslint-disable no-console */
const { src, dest } = require( 'gulp' );
const fs = require( 'fs' );
const globby = require( 'globby' );
const path = require( 'path' );
const zip = require( 'gulp-zip' );
const request = require( 'request' );
const po2json = require( 'gulp-po2json' );
const through = require( 'through2' );
const he = require( 'he' );
const download = require( 'download' );
const pkg = require( './package.json' );
const config = require( './.config.json' ); // Local config

const LOCALE_PERCENT_COMPLETE = 40;
const AVAILABLE_LANGUAGES_URL = 'https://translate.wordpress.org/api/projects/wp-plugins/search-regex/dev';
const LOCALE_URL = 'https://translate.wordpress.org/projects/wp-plugins/search-regex/dev/$LOCALE/default/export-translations?format=';
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
	'!tsconfig.json',
	'!search-regex.js.LICENSE.txt',
	'!jsconfig.json',
	'!api-footer.md',
	'!api-header.md',
	'!*.zip',
	'!src',
	'!src/**',
	'!phpstan.neon'
];

function downloadLocale( locale, wpName, type ) {
	const url = LOCALE_URL.replace( '$LOCALE', locale ) + type;

	download( url, 'languages', {
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
	return src( [ 'languages/*.po' ] )
		.pipe( po2json() )
		.pipe( through.obj( ( file, enc, cb ) => {
			const json = JSON.parse( String( file.contents ) );

			json[ '' ] = {
				'plural-forms': json[ '' ][ 'plural-forms' ]
			};

			Object.keys( json ).forEach( key => {
				if ( Array.isArray( json[ key ] ) ) {
					json[ key ] = json[ key ].filter( Boolean );
				}
			} );

			const translation = {
				"locale_data": {
					"search-regex": json,
				},
				"translation-revision-date": new Date().toISOString(),
				"source": "search-regex",
				"domain": "search-regex",
				"generator": "Search Regex",
			};
			file.contents = new Buffer( he.decode( JSON.stringify( translation ) ) );
			cb( null, file );
		} ) )
		.pipe( dest( 'languages/json/' ) )
		.on( 'end', function () {
			done();
		} );
}


function potDownload( cb ) {
	console.log( 'Requesting locales from: ' + AVAILABLE_LANGUAGES_URL );
	request( AVAILABLE_LANGUAGES_URL, ( error, response, body ) => {
		if ( error || response.statusCode !== 200 ) {
			console.error( 'Failed to download available languages from ' + AVAILABLE_LANGUAGES_URL, error );
			return;
		}

		const json = JSON.parse( body );

		for ( let x = 0; x < json.translation_sets.length; x++ ) {
			const locale = json.translation_sets[ x ];

			if ( parseInt( locale.percent_translated, 10 ) > 0 ) {
				console.log( 'Locale ' + locale.wp_locale + ' is ' + locale.percent_translated + '% translated' );
			}

			if ( parseInt( locale.percent_translated, 10 ) > LOCALE_PERCENT_COMPLETE ) {
				console.log( 'Downloading ' + locale.locale + ' - ' + locale.percent_translated + '%' );

				downloadLocale( locale.locale, locale.wp_locale, 'po' );
				downloadLocale( locale.locale, locale.wp_locale, 'mo' );
			}
		}

		cb();
	} );
}

exports.svn = svn;
exports.plugin = plugin;
exports.potDownload = potDownload;
exports.potJson = potJson;
