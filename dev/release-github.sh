#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

BRANCH="$(git branch --show-current)"

if [ "$BRANCH" != "trunk" ]; then
	echo "Releases must be created from trunk. Current branch: $BRANCH" >&2
	exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
	echo "Releases require a clean working tree." >&2
	exit 1
fi

VERSION="$(node -p "require('./package.json').version")"
TAG="$VERSION"
ZIP_FILE="release/search-regex.zip"

printf 'Create GitHub release for version %s from branch %s? [y/N] ' "$VERSION" "$BRANCH"
read -r CONFIRM

case "$CONFIRM" in
	[yY]|[yY][eE][sS])
		;;
	*)
		echo "Release cancelled."
		exit 1
		;;
esac

pnpm plugin:zip

if git show-ref --verify --quiet "refs/tags/$TAG"; then
	echo "Git tag $TAG already exists locally."
else
	git tag -a "$TAG" -m "Release $TAG"
fi

git push origin "$TAG"

gh release create "$TAG" "$ZIP_FILE" \
	--verify-tag \
	--title "$TAG" \
	--generate-notes
