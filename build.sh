#!/bin/bash

#
#Script for packaging Transposh's plugin for WordPress
#

VERSION=$1;

if [ -z $VERSION ]; then
    echo "Must enter a version number !!!"
    echo "Usage: $0 0.1.0"
    exit
fi

TMP_DIR="tmp"
TRANSPOSH_DIR=$TMP_DIR/transposh


echo "Building package for WordPress plugin version: $VERSION";
 
#Cleanup tmp dir 
rm -r $TMP_DIR 2>/dev/null
mkdir $TMP_DIR
mkdir $TRANSPOSH_DIR
echo "cleaned up $TMP_DIR directory"
echo

#
#Add sub directories
#
for DIR in flags js; do
    cp -r $DIR $TRANSPOSH_DIR
    echo "added sub-directory $DIR"
done;
echo

#
#Add non-php files 
#
for FTYPE in css png txt; do
    cp *.$FTYPE $TRANSPOSH_DIR
    echo "added $FTYPE files"
done;
echo

#
#Add php files while removing logging operations
#
echo "Adding .php files (without logging)"

for file in `find . -maxdepth 1 -iname '*.php' -printf "%p "`; do 
    sed 's/logger.*;//' $file > $TRANSPOSH_DIR/$file
    echo "added $file"
done;
echo

#
#Generate zip file
# 
cd $TMP_DIR
zip -r "transposh.$VERSION.zip" .
cd - >/dev/null
mv "$TMP_DIR/transposh.$VERSION.zip" . 

echo
echo "transposh.$VERSION.zip is ready"