#!/bin/bash
#
# This script is used to implement a workaround so that this plugin can be installed on WordPress, which
#  does not support plugins using other components via Composer (for additional information see:
#  https://wptavern.com/a-narrative-of-using-composer-in-a-wordpress-plugin). The workaround consists
#  of moving all the dependency packages (after they are properly installed by Composer) into the
#  namespace of the plugin itself. The dependency packages installed by Composer are left intact (under
#  the vendor/ directory). A new directory named thirdparty/ is created (as a copy of the vendor/
#  directory tree) and the sources files in there are edited to make the necessary namespace changes.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
THIRDPARTY_DIR="$SCRIPT_DIR/../thirdparty"
PLUGIN_SLUG="catenis-api-client"

if [[ -d "$THIRDPARTY_DIR" ]]; then
    echo "Third party directory already exists. Emptying it"
    rm -rf "$THIRDPARTY_DIR"/*
else
    echo "Third party directory does NOT exist yet. Creating it"
    mkdir "$THIRDPARTY_DIR"
fi

cp -R "$SCRIPT_DIR"/../vendor/* "$THIRDPARTY_DIR"/
# Make sure that .git directories are removed
rm -rf $(find "$THIRDPARTY_DIR" -name .git -type d -print)

echo -n "Fixing namespace of third party packages... "

# Change Internal Field Separator special environment variable so `for` loops can correctly
#  iterate over file paths containing spaces
IFS=$(echo -en "\n\b")

# Change namespace of third-party components moving them under our own plugin namespace
for f in $(find "$THIRDPARTY_DIR" -name "*.php" -print); do
   sed -i "" -E "s/^namespace +([A-Za-z][A-Za-z0-9_\\]*);/namespace Catenis\\\WP\\\\\1;/g" "$f"
   sed -i "" -E "s/^use +([A-Za-z][A-Za-z0-9_]*\\\)/use Catenis\\\WP\\\\\1/g" "$f"
done

for f in $(egrep -rl --exclude-dir composer --include "*.php" "([^A-Za-z0-9_]\\\\|'|\"| )(Clue|GuzzleHttp|WyriHaximus|React|RingCentral|Ratchet)\\\\" "$THIRDPARTY_DIR"/*); do
    sed -i "" -E "s/('|\"| |\t)(\\\\{0,2})(Clue|GuzzleHttp|WyriHaximus|React|RingCentral|Ratchet)\\\\/\1\2Catenis\\\\WP\\\\\3\\\\/g" "$f"
done

sed -i "" -E "s/( +')(([A-Za-z][A-Za-z0-9_]*\\\\\\\)+')/\1Catenis\\\\\\\WP\\\\\\\\\2/g" "$THIRDPARTY_DIR"/composer/autoload_{psr4,static}.php

sed -i "" -E "s/( +')Catenis\\\\\\\WP\\\\\\\Catenis\\\\\\\WP\\\\\\\'/\1Catenis\\\\\\\WP\\\\\\\'/g" "$THIRDPARTY_DIR"/composer/autoload_{psr4,static}.php

sed -i "" -E "s/( +')Evenement'/\1Catenis\\\\\\\WP\\\\\\\Evenement'/g" "$THIRDPARTY_DIR"/composer/autoload_{namespaces,static}.php

sed -i "" -E "s/('| | \\\\|\(\\\\)Composer\\\\Autoload/\1Catenis\\\\WP\\\\Composer\\\\Autoload/g" "$THIRDPARTY_DIR"/composer/autoload_real.php

# Properly reindex PSR0 and PSR4 components (used by Composer autoloader) to match the changed namespace
sed -i "" -e ':a' -e 'N' -e '$!ba' -e "s/\(\n\) \{1,\}),\n \{1,\}'[A-Z]' => \n \{1,\}array (\n/\1/g" "$THIRDPARTY_DIR"/composer/autoload_static.php
sed -i "" -e "s/^\( \{1,\}'\)[A-Z]\(' => \)$/\1C\2/g" "$THIRDPARTY_DIR"/composer/autoload_static.php
sed -i "" -e "s/ => \([0-9]\{1,\}\),$/ => \1+11,/g" "$THIRDPARTY_DIR"/composer/autoload_static.php
sed -i "" -e "s/\('Catenis\\\\\\\\WP\\\\\\\\' => 11\)+11/\1/" "$THIRDPARTY_DIR"/composer/autoload_static.php

# Change (localize) IDs of functions files loaded by Composer autoloader
sed -i "" -E "s/^(( |\t)+)'([a-f0-9]{32})' =>/\1'${PLUGIN_SLUG}_\3' =>/" "$THIRDPARTY_DIR"/composer/autoload_{files,static}.php

# Redefine directory paths of PSR0 components
mkdir "$THIRDPARTY_DIR"/evenement/evenement/src/Catenis && mkdir "$THIRDPARTY_DIR"/evenement/evenement/src/Catenis/WP && mv "$THIRDPARTY_DIR"/evenement/evenement/src/Evenement "$THIRDPARTY_DIR"/evenement/evenement/src/Catenis/WP/

# Add missing namespace
sed -i "" '2i\
namespace Catenis\\WP;
' "$THIRDPARTY_DIR"/ralouphie/getallheaders/src/getallheaders.php

sed -i "" -E "s/'getallheaders'/'Catenis\\\\WP\\\\getallheaders'/" "$THIRDPARTY_DIR"/ralouphie/getallheaders/src/getallheaders.php

for f in $(find "$THIRDPARTY_DIR"/guzzlehttp -name "*.php" -print); do
    sed -i "" -E "s/getallheaders\(/Catenis\\\\WP\\\\getallheaders(/" "$f"
done

# Patch code to avoid security threat warning from VaultPress
sed -i "" -E "s/^(( |\t)+)return \\\$handler\\(\\\$fn\\(\\\$request), \\\$options);$/\1\\\$fn_result = \\\$fn\\(\\\$request); return \\\$handler\\(\\\$fn_result, \\\$options);/" "$THIRDPARTY_DIR"/guzzlehttp/guzzle/src/Middleware.php
sed -i "" -E "s/^(( |\t)+)\\\$notify\\(\\\$onProgress\\(\\\$update));$/\1\\\$onProgress_result = \\\$onProgress\\(\\\$update); \\\$notify(\\\$onProgress_result);/" "$THIRDPARTY_DIR"/react/promise/src/Promise.php

echo "Done."