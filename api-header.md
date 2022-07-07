Search Regex is a WordPress plugin to perform search and replace operations on your WordPress data. It exposes a set of REST API endpoints. These are used by the Search Regex plugin itself, and can also be used by anyone else.

## REST API Endpoints

The API endpoints are available on the WordPress site at `https://yoursite.com/wp-json/search-regex/v1`.

The examples below reference `https://searchregex.com` and you should substitute this for your own site.

Body parameters are supplied as JSON.

## Authentication

All requests must be authenticated by someone with `manage_options` capabilities. See the [REST API authentication guide](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/).

<div id="version" style="display: none"><strong>1.0.0</strong></a>
