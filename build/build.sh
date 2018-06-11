#!/bin/bash

#
#Script for packaging Transposh's plugin for WordPress
#

VERSION=$1;
ZIPME=$2;
COPYTO=$3;
MINIFY=true

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
for DIR in js css img langs widgets wp; do
  cp -r $DIR $TRANSPOSH_DIR
  echo "added sub-directory $DIR"
done;
echo

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
done;
echo

#
# Init replacement vars
#
DATE=`date -R`
YEAR=`date +%Y`

#
# Add php files while processing versions
#
  echo "Adding .php files (with logging)"
  for file in `find . -maxdepth 4 -iname '*.php' -not -path "./build/*" -not -path "./resources/*" -not -path "./test/*"`; do 
    sed "s/%VERSION%/$VERSION/;s/%DATE%/$DATE/;s/%YEAR%/$YEAR/;" $file > $TRANSPOSH_DIR/$file
#///hm,mm!
    php build/generateversions.php $TRANSPOSH_DIR/$file full > /tmp/inp_nope
    cp /tmp/inp_nope $TRANSPOSH_DIR/$file
    rm /tmp/inp_nope
#    cp $file $TRANSPOSH_DIR/$file
    echo "added $file"
  done;
#fi
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

#if [ "$DEBUG" != 'debug' ]; then
if [ $MINIFY == true ]; then
  echo "Minify .js files"
  for file in `find ./js -maxdepth 3 -iname '*.js' ! -name keyboard.js ! -name lazy.js ! -name jquery.ui.menu.js`; do
    echo "minifying $file"
#    java -jar /root/yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar $file -o $TRANSPOSH_DIR/$file
echo "/*
 * Transposh v$VERSION
 * http://transposh.org/
 *
 * Copyright $YEAR, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: $DATE
 */" > $TRANSPOSH_DIR/$file
    java -jar /root/googlecompiler/compiler.jar --source_map_include_content --source_map_format=V3 --create_source_map $TRANSPOSH_DIR/$file.map --js $file --js_output_file $TRANSPOSH_DIR/$file
    echo "//# sourceMappingURL=`basename $file`.map" >> $TRANSPOSH_DIR/$file
#    java -jar /root/googlecompiler/compiler.jar --js $file >> $TRANSPOSH_DIR/$file
  done;
# handle the third party .js and honor their copyrights
  head -n 13 js/lazy.js > $TRANSPOSH_DIR/js/lazy.js
  java -jar /root/googlecompiler/compiler.jar --js js/lazy.js --strict_mode_input false  >> $TRANSPOSH_DIR/js/lazy.js
  head -n 13 js/jquery.ui.menu.js > $TRANSPOSH_DIR/js/jquery.ui.menu.js
  java -jar /root/googlecompiler/compiler.jar --js js/jquery.ui.menu.js >> $TRANSPOSH_DIR/js/jquery.ui.menu.js
  head -n 57 js/keyboard.js > $TRANSPOSH_DIR/js/keyboard.js
  java -jar /root/googlecompiler/compiler.jar --js js/keyboard.js >> $TRANSPOSH_DIR/js/keyboard.js

  echo "Minify .css files"
  for file in `find . -maxdepth 2 -iname '*.css'`; do 
    echo "minifying $file"
    java -jar /root/yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar $file -o $TRANSPOSH_DIR/$file
  done;
fi

# Remove .svn dirs
##echo "removed .svn dirs"
##find $TRANSPOSH_DIR -name "*.svn*" -exec rm -rf {} 2>/dev/null \;

#
#Generate zip file
# 
if [ "$ZIPME" == 'zip' ]; then
  cd $TRANSPOSH_DIR
  cd ..
  zip -9rq "transposh.$VERSION.zip" transposh-translation-filter-for-wordpress
  advzip -z4 "transposh.$VERSION.zip"
  cd - >/dev/null
#  mv "$TRANSPOSH_DIR/transposh.$VERSION.zip" . 
  echo
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