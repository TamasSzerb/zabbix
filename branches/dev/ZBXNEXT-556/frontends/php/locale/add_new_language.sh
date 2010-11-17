#!/bin/bash

messagetemplate=frontend.pot

[[ $1 ]] || {
	echo "Specify language code"
	exit 1
}

[ -f $messagetemplate ] && {
	mkdir -p $1/LC_MESSAGES
	msginit --no-translator --no-wrap --locale=$1 --input=frontend.pot \
	-o $1/LC_MESSAGES/frontend.po
} || {
	echo "po template $messagetemplate missing"
}
