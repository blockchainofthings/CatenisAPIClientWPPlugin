#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
PLUGIN_VERSION="$(grep "^Version: \+[0-9.]\+" "$SCRIPT_DIR"/../catenis-api-client.php | grep -o "[0-9.]\+")"
BUILD_DIR="$SCRIPT_DIR/../build/$PLUGIN_VERSION"

if [ -f "$BUILD_DIR"/catenis-api-client.zip ]; then
    echo "Plugin version ($PLUGIN_VERSION) already built"
    exit -1
fi

if [ ! -d "$BUILD_DIR" ]; then
    mkdir "$BUILD_DIR"
fi

cd "$SCRIPT_DIR"/..
zip -r "$BUILD_DIR"/catenis-api-client.zip ./* -x ./.\* \*.DS_Store ./\*.code-workspace ./composer.\* svn/\* vendor/\* build/\* bin/\* log/\* io/\* ./CHANGELOG.md ./README.md