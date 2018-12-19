#!/bin/bash
#
# This script is used to implement a workaround so that this plugin can be installed on WordPress, which
#  does not support plugins using other components via Composer (for additional information see:
#  https://wptavern.com/a-narrative-of-using-composer-in-a-wordpress-plugin). The workaround consists
#  of moving all the dependent packages (after they are properly installed by Composer) into the
#  namespace of the plugin itself. The dependent packages installed by Composer are left intact (under
#  the vendor/ directory). A new directory named thirdparty/ is created (as a copy of the vendor/
#  directory tree) and the sources files in there are edited to make the necessary namespace changes.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
THIRDPARTY_DIR="$SCRIPT_DIR/../thirdparty"

if [[ -d $THIRDPARTY_DIR ]]; then
    echo "Third party directory already exists. Emptying it"
    rm -r $THIRDPARTY_DIR/*
else
    echo "Third party directory does NOT exist yet. Creating it"
    mkdir $THIRDPARTY_DIR
fi

cp -R $SCRIPT_DIR/../vendor/* $THIRDPARTY_DIR/

echo -n "Fixing namespace of third party packages... "

for f in $(find $THIRDPARTY_DIR -name "*.php" -print); do
   sed -i "" -E "s/^namespace +([A-Za-z][A-Za-z0-9_\\]*);/namespace Catenis\\\WP\\\\\1;/g" $f
   sed -i "" -E "s/^use +([A-Za-z][A-Za-z0-9_]*\\\)/use Catenis\\\WP\\\\\1/g" $f
done

for f in $(find $THIRDPARTY_DIR/clue -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )Clue\\\\/\1Catenis\\\\WP\\\\Clue\\\\/g" $f
done

for f in $(find $THIRDPARTY_DIR/guzzlehttp -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )GuzzleHttp\\\\/\1Catenis\\\\WP\\\\GuzzleHttp\\\\/g" $f
done

for f in $(find $THIRDPARTY_DIR/wyrihaximus -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )WyriHaximus\\\\/\1Catenis\\\\WP\\\\WyriHaximus\\\\/g" $f
done

for f in $(find $THIRDPARTY_DIR/react -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )React\\\\/\1Catenis\\\\WP\\\\React\\\\/g" $f
done

for f in $(find $THIRDPARTY_DIR/ringcentral -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )RingCentral\\\\/\1Catenis\\\\WP\\\\RingCentral\\\\/g" $f
done

for f in $(find $THIRDPARTY_DIR/ratchet -name "*.php" -print); do
   sed -i "" -E "s/([^A-Za-z0-9_]\\\\|'| )Ratchet\\\\/\1Catenis\\\\WP\\\\Ratchet\\\\/g" $f
done

sed -i "" -E "s/( +')(([A-Za-z][A-Za-z0-9_]*\\\\\\\)+')/\1Catenis\\\\\\\WP\\\\\\\\\2/g" $THIRDPARTY_DIR/composer/autoload_{psr4,static}.php

sed -i "" -E "s/( +')Catenis\\\\\\\WP\\\\\\\Catenis\\\\\\\WP\\\\\\\'/\1Catenis\\\\\\\WP\\\\\\\'/g" $THIRDPARTY_DIR/composer/autoload_{psr4,static}.php

sed -i "" -E "s/( +')Evenement'/\1Catenis\\\\\\\WP\\\\\\\Evenement'/g" $THIRDPARTY_DIR/composer/autoload_{namespaces,static}.php

sed -i "" -E "s/('| | \\\\|\(\\\\)Composer\\\\Autoload/\1Catenis\\\\WP\\\\Composer\\\\Autoload/g" $THIRDPARTY_DIR/composer/autoload_real.php

# Add missing namespace
sed -i "" '2i\
namespace Catenis\\WP;
' $THIRDPARTY_DIR/ralouphie/getallheaders/src/getallheaders.php

sed -i "" -E "s/'getallheaders'/'Catenis\\\\WP\\\\getallheaders'/" $THIRDPARTY_DIR/ralouphie/getallheaders/src/getallheaders.php

for f in $(find $THIRDPARTY_DIR/guzzlehttp -name "*.php" -print); do
    sed -i "" -E "s/getallheaders\(/Catenis\\\\WP\\\\getallheaders(/" $f
done

echo "Done."