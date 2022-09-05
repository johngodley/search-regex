=== Search Regex ===
Contributors: johnny5
Donate link: http://searchregex.com/donation/
Tags: search, replace, regex, regular expression, database, post, page
Requires at least: 5.6
Tested up to: 6.0.2
Stable tag: trunk
Requires PHP: 5.6
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

== Changelog ==

= 3.0.6 - September 5th 2022 =
* Fix incorrect pagination
* Fix incorrect page reload
* Fix export missing a page of data

= 3.0.5 - August 25th 2022 =
* Fix empty replacement string when saving a preset
* Fix preset being saved from search preset dropdown
* Fix search flags being reset when no replacement

= 3.0.4 - August 19th 2022 =
* Fix 'update preset' dropdown menu not updating
* Fix current preset when switching pages

= 3.0.3 - August 14th 2022 =
* Fix error in error message
* Fix error when removing text
* Fix update notice never going away
* Fix multi-line replace not replacing

= 3.0.2 - July 28th 2022 =
* Fix more issues with older PHP
* Fix a failed update not showing an error message
* Fix post meta not updating

= 3.0.1 - July 26th 2022 =
* Fix issue with PHP < 7.4
* Fix ID appearing on date values
* Fix a global search on options with no filter causing a bad query

= 3.0 - July 24th 2022 =
- Major update with many, many, new features
- Advanced searches with fully customisable filters that can be combined together with AND and OR logic
- Search any WordPress table, including posts, comments, users, meta-data, and options
- Search any column, including text, numeric, and date
- Can be used as a general-purpose data viewer and data updater
- Export rows to CSV and SQL
- Mass delete matching rows
- Modify any column, with ability to increment and decrement numbers and dates (i.e. add 2 hours to post modification date)
- Insert data from other columns. For example, add a list of categories into a post, add post date to the title etc
- Transform existing data. For example, change case, convert underscores to dashes.
- Full support for saving advanced filter and actions to presets
- Autocomplete filters and modifications
- Support for serialized data (with conditions)
- Fix replace with dollar ($) from being treated as a regular expression

= 2.4.1 - 29th November 2020 =
- Fix replacements with a backslashed character

= 2.4 - 29th October 2020 =
- Support multi-line search phrases
- Improve progress bar animation
- Add option to ignore draft posts
- Improve support for serialized data - show it in the UI, and don't show an error
- Fix source flags being sent when source is changed

= 2.3.3 - 13th September 2020 =
- Fix replace in 'all post types'
- Fix duplicate sources when deselecting a post source

= 2.3.2 - 8th September 2020 =
- Fix locale pluralization throwing an error

= 2.3.1 - 7th September 2020 =
- Fix locales not loading
- Fix startup error on some sites

= 2.3 - 6th September 2020 =
- Add option to set a default preset
- Fix global replace not working in post meta
- Fix replace value not being used in a preset

= 2.2.1 - 22nd July 2020 =
- Fix regular expression search having no pagination buttons
- Fix saving a preset with no search phrase showing an error
- Fix inline editor not working on sources with an underscore
- Add edit link for TablePress tables
- Increase maximum per-page size

= 2.2 - 18th July 2020 =
- Add presets
- Save a search as a preset
- Lock fields in a search
- Create custom search templates
- Fix post meta search

= 2.1 - 6th June 2020 =
- Support searching and replacing in multiple sources
- Improve regex search and replace speed
- Row actions have moved to a dropdown
- Fix HTML entities in row titles
- Handle unknown post types
- Fix global replace showing 0% progress
- Add Japanese locale
- Add Dutch locale

= 2.0.1 - 11th May 2020 =
- Comment title now takes you to comment page
- Improve regex performance when data has large gaps
- Use correct contact address
- Support \1 as well as $1 in regular expression captures

= 2.0 - 9th May 2020 =
- Release version 2.0, a ground-up rewrite
- Handles any size of database without memory issues or server timeouts
- New and responsive UI with inline replacing of individual phrases
- Match replacements are updated in real-time, including regular expressions
- Edit an entire database row with the inline editor
- Supports custom post types
- Groups columns from one database together
- Search in third-party plugins (currently Redirection)
- Improved regular expressions without needing delimiters

= 1.4.16 - 21st Nov 2014 and before to 2007 =
- Old versions
