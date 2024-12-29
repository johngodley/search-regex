=== Search Regex ===
Contributors: johnny5
Donate link: http://searchregex.com/donation/
Tags: search, replace, regex, regular expression, database
Tested up to: 6.7
Stable tag: 3.1.2
License: GPLv3

Search Regex adds a powerful set of search and replace functions to WordPress posts, pages, custom post types, and other data.

== Description ==

Search Regex adds a powerful set of search and replace functions to WordPress posts, pages, custom post types, and other data sources. These go beyond the standard searching capabilities, and allow you to search and replace almost any data stored on your site. In addition to simple searches you have the full power of PHP's regular expressions at your disposal.

You can use this to do things like:
- Help migrate a site from one domain to another
- Update URLs in links and images
- Perform site-wide changes

Search filters can be created to match any column of any WordPress table. For example:
- Find all posts in a category
- Find all post meta data without a post
- Find all posts in a date range

Search Regex handles small and large sites.

Search Regex has been tested with PHP from 7.0 to 8.3.

== What can I search? ==

You can search:
- Posts
- Pages
- Any custom post type
- Comments (including spam)
- Users
- Meta data
- WordPress options
- Supported plugins (such as Redirection)

Full regular expression support is provided, and you can capture data and use it in the replacement.

== Actions ==

A variety of actions can be performed on matching results:
- Modify and replace the result
- Delete the result
- Export to CSV and SQL
- Perform custom PHP action

== Modify and Replace Data ==

Results can be individually modified by clicking on the item to change, and using the popup modifier.

Bulk modifications can be performed against all matching results.

The types of modification depend on the data being changed:
- Numbers can be added or subtracted to existing numbers
- Hours, days, weeks, and months, can be added to dates
- Tags and categories can be added, removed, and substitued

Custom shortcodes are supported that allow dynamic data to be included:
- Add the current date and time, in any format, to content
- Insert data from other columns. For example, insert the category into the title
- Transform existing data. For example, convert case, change underscores to dashes.

== Example uses ==

Some ideas for potential uses:
- Delete all comments that match a phrase
- Add a category to all matching posts
- Remove orphaned meta data

== Support ==

Please submit bugs, patches, and feature requests to:

[https://github.com/johngodley/search-regex](https://github.com/johngodley/search-regex)

Please submit translations to:

[https://translate.wordpress.org/projects/wp-plugins/search-regex](https://translate.wordpress.org/projects/wp-plugins/search-regex)

== Installation ==

The plugin is simple to install:

1. Download `search-regex.zip`
1. Unzip
1. Upload `search-regex` directory to your `/wp-content/plugins` directory
1. Go to the plugin management page and enable the plugin
1. Configure the options from the `Tools/Search Regex` page

You can find full details of installing a plugin on the [plugin installation page](https://searchregex.com/support/installation/).

== Translations ==

Translations are provided by WordPress itself. You can update the translations by going to the Dashboard > Updates section of WP admin.

== Screenshots ==

1. Search options
2. Inline search and replace
3. Inline editor

== Documentation ==

Full documentation can be found on the [Search Regex](http://searchregex.com/) site.

== Upgrade Notice ==

= 2.0 =
* Entirely new rewrite of the plugin. Requires minimum PHP 5.6 and is compatible with all versions (tested up to 8.0).

= 3.0 =
* Major update adding advanced search filters, modifications to any column, and a variety of actions (including exporting to SQL).

= 3.1.0 =
* Set PHP 7.0 and WP 6.4 as the baseline. Plugin now uses WP supplied translations

== Changelog ==

= 3.1.2 - December 29th 2024 =
* Fix crash when loading from a preset with an 'includes any'

= 3.1.1 - November 23rd 2024 =
* Update for WordPress 6.7

= 3.1.0 - August 8th 2024 =
* Update for latest WP
* Fix export of regular expression result
* Switch to WP core translations
* See changelog.txt for more details
