#!/bin/bash

echo "Generating translation template..."

# xgettext will be used on all php files

cd $(dirname $0)/..
directory=$PWD
find ./ -type f -name '*.php' | sort -d -f > locale/POTFILES.in

# keyword "_n" is Zabbix frontend plural function
# keyword "_s" is Zabbix frontend placeholder function
# keyword "_x" is Zabbix frontend context function
xgettext --files-from=locale/POTFILES.in --from-code=UTF-8 \
--output=locale/frontend.pot \
--copyright-holder="Zabbix SIA" --no-wrap --sort-output \
--add-comments="GETTEXT:" --keyword=_n:1,2 --keyword=_s \
--keyword=_x:1,2c || exit 1

cd $directory/locale
#--sort-by-file

echo "Merging new strings in po files..."

for i in */LC_MESSAGES/frontend.po; do
	echo -n "$i" | cut -d/ -f1
	# fuzzy matching provides all kinds of interesting results, for example,
	# "NTLM authentication" is translated as "LDAP-Authentifizierung" - thus
	# it is disabled
	msgmerge --no-fuzzy-matching --no-wrap --update \
--backup=off "$i" frontend.pot
done

# 'msgattrib --no-obsolete' could be used to automatically drop obsolete strings

for i in */LC_MESSAGES/frontend.po; do
	echo -ne "$i\t"
	# setting output file to /dev/null so that unneeded messages.mo file
	# is not created
	msgfmt -c --statistics -o /dev/null $i
done
