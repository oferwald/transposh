#!/bin/bash

#
#Script for packaging Transposh's plugin for WordPress
#

VERSION=$1;
DEBUG=$2;
ZIPME=$3;

if [ -z $VERSION ]; then
  echo "Must enter a version number !!!"
  echo "Usage: $0 0.1.0 [[debug] zip]"
  exit
fi

TRANSPOSH_DIR=/tmp/transposh

echo "Building package for WordPress plugin version: $VERSION";
 
#Cleanup tmp dir 
rm -r $TRANSPOSH_DIR 2>/dev/null
mkdir $TRANSPOSH_DIR
echo "cleaned up $TRANSPOSH_DIR directory"
echo

#
#Add sub directories
#
for DIR in js css img; do
  cp -r $DIR $TRANSPOSH_DIR
  echo "added sub-directory $DIR"
done;
echo

#
#Create core directory
#
mkdir $TRANSPOSH_DIR/core

#
#Add non-php files 
#
for FTYPE in png txt; do
  cp *.$FTYPE $TRANSPOSH_DIR
  echo "added $FTYPE files"
done;
echo

#
#Add php files while removing logging operations
#
if [ "$DEBUG" != 'debug' ]; then
  echo "Adding .php files (without logging)"
  for file in `find . -maxdepth 2 -iname '*.php'`; do 
    sed "s/logger.*;//;s/require_once(\"core.logging.*//;s/require_once(\"logging.*//;s/<%VERSION%>/$VERSION/;" $file > $TRANSPOSH_DIR/$file
    echo "added $file"
  done;
else
  echo "Adding .php files (with logging)"
  for file in `find . -maxdepth 2 -iname '*.php'`; do 
    cp $file $TRANSPOSH_DIR/$file
    echo "added $file"
  done;
fi
echo

#
#fixing version in readme.txt
#
sed "s/<%VERSION%>/$VERSION/;" readme.txt > $TRANSPOSH_DIR/readme.txt
echo "fixing version in readme.txt to $VERSION"

#
# Remove logging.php
#
if [ "$DEBUG" != 'debug' ]; then
  rm $TRANSPOSH_DIR/core/logging.php
  echo "removed logging.php"
  rm $TRANSPOSH_DIR/core/FirePHP.class.php
  echo "removed FirePHP.class.php"
else
  rm $TRANSPOSH_DIR/screenshot*.png
  echo "removed screenshots"
fi

echo "Minify .js files"
for file in `find . -maxdepth 2 -iname '*.js'`; do 
  echo "minifying $file"
  java -jar /root/yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar $file -o $TRANSPOSH_DIR/$file
done;

echo "Minify .css files"
for file in `find . -maxdepth 2 -iname '*.css'`; do 
  echo "minifying $file"
  java -jar /root/yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar $file -o $TRANSPOSH_DIR/$file
done;

# Remove .svn dirs
find $TRANSPOSH_DIR -name "*.svn*" -exec rm -rf {} 2>/dev/null \;
echo "removed .svn dirs"

#
#Generate zip file
# 
if [ "$ZIPME" == 'zip' ]; then
  cd $TRANSPOSH_DIR
  zip -rq "transposh.$VERSION.zip" .
  cd - >/dev/null
#  mv "$TRANSPOSH_DIR/transposh.$VERSION.zip" . 
  echo
  echo "transposh.$VERSION.zip is ready"
fi
