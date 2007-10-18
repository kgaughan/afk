#!/bin/sh
for i in `find . -name \*.php`; do php -l "$i"; done
