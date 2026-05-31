const { FlatCompat } = require( '@eslint/eslintrc' );
const tanstackQuery = require( '@tanstack/eslint-plugin-query' );

const compat = new FlatCompat( {
	baseDirectory: __dirname,
} );

module.exports = [
	{
		ignores: [
			'build/**',
			'node_modules/**',
			'release/**',
			'vendor/**',
		],
	},
	...compat.config( {
		extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
		env: {
			browser: true,
			jest: true,
		},
		rules: {
			'react-hooks/rules-of-hooks': 'error',
			'react-hooks/exhaustive-deps': 'warn',
		},
	} ),
	...tanstackQuery.configs[ 'flat/recommended' ],
];
