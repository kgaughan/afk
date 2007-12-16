#!/bin/sh
for i in `find . -name \*.php -o -name \*.inc`; do
	php -l "$i"
done
