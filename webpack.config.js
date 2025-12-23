/**
 * External dependencies
 */

const fs = require( 'fs' );
const path = require( 'path' );
const webpack = require( 'webpack' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const pkg = require( './package.json' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const WebpackShellPluginNext = require( 'webpack-shell-plugin-next' );
const crypto = require( 'crypto' );
const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );

const versionHeader = ( md5 ) => `<?php

define( 'SEARCHREGEX_VERSION', '${ pkg.version }' );
define( 'SEARCHREGEX_BUILD', '${ md5 }' );
define( 'SEARCHREGEX_MIN_WP', '${ pkg.wordpress.supported }' );
`;

function generateVersion() {
	fs.readFile( path.resolve( __dirname, 'build/search-regex.js' ), ( error, data ) => {
		const md5 = crypto.createHash( 'md5' ).update( data, 'utf8' ).digest( 'hex' );

		fs.writeFileSync( path.resolve( __dirname, 'build/search-regex-version.php' ), versionHeader( md5 ) );
	} );
}

const modified = {
	...defaultConfig,
	entry: {
		'search-regex': path.resolve( __dirname, 'src/index.tsx' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	externals: {
		'@wordpress/i18n': 'wp.i18n',
	},
	infrastructureLogging: {
		...defaultConfig.infrastructureLogging,
		level: 'error',
	},
	ignoreWarnings: [
		// Suppress Sass @import deprecation warnings
		( warning ) => warning.message && warning.message.includes( 'Sass @import' ),
	],
	plugins: [
		// Replace the default MiniCSSExtractPlugin with a custom one that doesn't externalise React
		...defaultConfig.plugins.filter( ( plugin ) => ! ( plugin instanceof MiniCSSExtractPlugin ) ),
		new MiniCSSExtractPlugin( { filename: 'search-regex.css' } ),

		new webpack.DefinePlugin( {
			'process.env': { NODE_ENV: JSON.stringify( process.env.NODE_ENV || 'development' ) },
			SEARCHREGEX_VERSION: "'" + pkg.version + "'",
		} ),

		new WebpackShellPluginNext( {
			onBuildEnd: {
				scripts: [ generateVersion ],
				blocking: true,
				parallel: false,
			},
		} ),

		// Add bundle analyzer in analyze mode
		...( process.env.ANALYZE
			? [
					new BundleAnalyzerPlugin( {
						analyzerMode: 'static',
						reportFilename: 'bundle-report.html',
						openAnalyzer: true,
					} ),
			  ]
			: [] ),
	],
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'@wp-plugin-components': path.resolve( __dirname, 'src/wp-plugin-components' ),
			'@wp-plugin-lib': path.resolve( __dirname, 'src/wp-plugin-lib/' ),
		},
	},
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			cacheGroups: {
				vendor: {
					test: /[\\/]node_modules[\\/]/,
					name: 'vendor',
					chunks: 'all',
				},
			},
		},
		minimizer: [
			new TerserPlugin( {
				parallel: true,
				terserOptions: {
					output: {
						comments: /translators:/i,
					},
					compress: {
						passes: 2,
					},
					mangle: {
						reserved: [ '__', '_n', '_nx', '_x' ],
					},
				},
				extractComments: {
					condition: true,
					banner: () => {
						return (
							'Search Regex v' + pkg.version + ' - please refer to license.txt for license information'
						);
					},
				},
			} ),
		],
	},
};

module.exports = modified;
