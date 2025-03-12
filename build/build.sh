#!/bin/bash

#
#Script for packaging Transposh's plugin for WordPress
#

VERSION=$1
ZIPME=$2
COPYTO=$3
WPO=$4
MINIFY=true

if [ -n "$WPO" ]; then
  echo "Version supplied $WPO"
else
  WPO="full"
fi

#
# Init replacement vars
#
DATE=`date -R`
YEAR=`date +%Y`

if [ $WPO == 'wporg' ]
then
  ZIPME=false
  DIRS="js css img langs wp"
else
  DIRS="js css img langs widgets wp"
fi

if [ -z $VERSION ]; then
  echo "Must enter a version number !!!"
  echo "Usage: $0 0.1.0 [[zip] [targetdir]]"
  exit
fi

TRANSPOSH_DIR=/tmp/transposh-translation-filter-for-wordpress

echo "Building package for WordPress plugin version: $VERSION";
 
#Cleanup tmp dir 
rm -r $TRANSPOSH_DIR 2>/dev/null
mkdir $TRANSPOSH_DIR
echo "cleaned up $TRANSPOSH_DIR directory"
echo

#
#Add sub directories
#
for DIR in $DIRS; do
  cp -r $DIR $TRANSPOSH_DIR
  echo "added sub-directory $DIR"
done
echo

if [ $WPO == 'wporg' ]
then
  echo "only add default widget"
  mkdir -p $TRANSPOSH_DIR/widgets/default
  cp -r widgets/default $TRANSPOSH_DIR/widgets/default
fi

#
#Create core directory
#

mkdir $TRANSPOSH_DIR/core
mkdir $TRANSPOSH_DIR/core/shd
mkdir $TRANSPOSH_DIR/core/jsonwrapper

#
#Add non-php files
#

for FTYPE in txt; do
  cp *.$FTYPE $TRANSPOSH_DIR
  echo "added $FTYPE files"
done
echo

#
# Add php files while processing versions
#

if [ $WPO == 'wporg' ]
then
  PHPFILES=`find . -maxdepth 4 -iname '*.php' -not -path "./build/*" -not -path "./resources/*" -not -path "./test/*" -not -path "./widgets/*"`
  PHPFILES+=" ./widgets/default/tpw_default.php"
else
  PHPFILES=`find . -maxdepth 4 -iname '*.php' -not -path "./build/*" -not -path "./resources/*" -not -path "./test/*"`
fi

  echo "Adding .php files"
  for file in $PHPFILES; do
    sed "s/%VERSION%/$VERSION/;s/%DATE%/$DATE/;s/%YEAR%/$YEAR/;" $file > $TRANSPOSH_DIR/$file
    if [ $WPO == 'wporg' ]
    then
      php build/generateversions.php $TRANSPOSH_DIR/$file wporg > /tmp/inp_nope
    else
      php build/generateversions.php $TRANSPOSH_DIR/$file full > /tmp/inp_nope
    fi
    cp /tmp/inp_nope $TRANSPOSH_DIR/$file
    rm /tmp/inp_nope
    echo "added $file"
  done;
echo

#
#Add the index.html
#

echo "Adding index.html"
cp index.html $TRANSPOSH_DIR/index.html

#
#fixing version in readme.txt
#

echo "fixing version in readme.txt to $VERSION"
sed "s/%VERSION%/$VERSION/;" readme.txt > $TRANSPOSH_DIR/readme.txt

# those are the files that are lazy loaded, we make sure they will be read by the browser with the sourceURL comment
LAZYLIST="transposhedit.js de.js es.js fa.js fr.js he.js it.js nl.js ru.js tr.js keyboard.js lazy.js jquery.ui.menu.js"
#if [ "$DEBUG" != 'debug' ]; then
if [ $MINIFY == true ]; then
  echo "Minify .js files"
  for file in `find ./js -maxdepth 3 -iname '*.js'`; do
    echo "minifying $file"
    BASENAME=`basename $file`
    DIRNAME=`dirname $file`
    uglifyjs --comments --compress --mangle --source-map "base='$DIRNAME',url='$BASENAME.map',includeSources" $file -o $TRANSPOSH_DIR/$file

    if [[ $LAZYLIST == *$BASENAME* ]] # lazy loaded?
    then
      echo -e -n "\n//# sourceURL=/wp-content/plugins/transposh-translation-filter-for-wordpress${DIRNAME:1}/$BASENAME" >> $TRANSPOSH_DIR/$file
    fi
  done

  echo "Minify .css files"
  for file in `find . -maxdepth 2 -iname '*.css'`; do 
    echo "minifying $file"
    java -jar /root/yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar $file -o $TRANSPOSH_DIR/$file
  done;
fi

#
#Generate zip file
#

if [ "$ZIPME" == 'zip' ]; then
  cd $TRANSPOSH_DIR
  cd ..
  echo "zipping..."
  zip -9rq "transposh.$VERSION.zip" transposh-translation-filter-for-wordpress
  echo "rezipping..."
  advzip -z4 "transposh.$VERSION.zip"
  cd - >/dev/null
  echo "transposh.$VERSION.zip is ready"
fi

#
#Copy to target
#

if [ "$COPYTO" != '' ]; then
  echo "should copy to $COPYTO"
  cd $TRANSPOSH_DIR
  cp -rp * $COPYTO
  cd - >/dev/null
fi