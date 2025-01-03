#!/bin/sh
VERSION=$1

# Make sure we have the repo
svn co https://plugins.svn.wordpress.org/search-regex/trunk svn

cd svn

# Make sure we have the latest version
svn up

# Sync the files
rsync --filter='P .svn' -avz --delete ../release/search-regex/ .

svn st

# Force user to enter the details
echo "Run 'svn ci' to commit the changes. You may need to 'svn rm' any files that are no longer needed, or 'svn add' ones that are new."

# Tag the release
echo "Then run: svn cp https://plugins.svn.wordpress.org/search-regex/trunk https://plugins.svn.wordpress.org/search-regex/tags/$VERSION -m \"Tagging version $VERSION\""
