# Search Regex

[![Build Status](https://travis-ci.org/johngodley/search-regex.svg?branch=master)](https://travis-ci.org/johngodley/search-regex)

Search Regex adds a powerful set of search and replace functions to WordPress posts, pages, custom post types, and other data sources. These go beyond the standard searching capabilities, and allow you to search and replace almost any data stored on your site. In addition to simple searches you have the full power of PHP's regular expressions at your disposal.

Note: this is the current 'trunk' version of Search Regex. It may be newer than what is in the WordPress.org plugin repository, and should be considered experimental.

## Installation
Search Regex can be installed by visiting the WordPress.org plugin page:

https://wordpress.org/plugins/search-regex/

## Building

Search Regex is mostly a PHP plugin, but does contain files that need to be built. For this you'll need Gulp, Node, and Yarn installed. Install required modules with:

`yarn install`

### React

Some parts of the UI are React and can be built with:

`yarn build`

To use in development mode run:

`yarn start`

### Releasing

Finally, to produce a release copy:

`gulp plugin`

## Support

Please raise any bug reports or enhancement requests here. Pull requests are always welcome.

You can find a more detailed description of the plugin on the [Search Regex home page](https://searchregex.com)

Translations can be added here:

https://translate.wordpress.org/projects/wp-plugins/search-regex
