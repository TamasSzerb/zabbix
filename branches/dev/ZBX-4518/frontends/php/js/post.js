/*
 ** Zabbix
 ** Copyright (C) 2000-2011 Zabbix SIA
 **
 ** This program is free software; you can redistribute it and/or modify
 ** it under the terms of the GNU General Public License as published by
 ** the Free Software Foundation; either version 2 of the License, or
 ** (at your option) any later version.
 **
 ** This program is distributed in the hope that it will be useful,
 ** but WITHOUT ANY WARRANTY; without even the implied warranty of
 ** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 ** GNU General Public License for more details.
 **
 ** You should have received a copy of the GNU General Public License
 ** along with this program; if not, write to the Free Software
 ** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

jQuery(function() {

	cookie.init();
	chkbxRange.init();

	if (jQuery(window).width() < 1024) {
		jQuery('head').append('<link rel="stylesheet" type="text/css" href="styles/handheld.css" />');
	}


	// tab switching via the anchor
	if (window.location.hash) {
		jQuery('.ui-tabs .ui-tabs-nav a[href="' + window.location.hash + '"]').trigger('click');
	}
	jQuery('.ui-tabs').bind('tabsselect', function(event, ui) {
		var href = ui.tab.href.split('#');
		window.location.hash = href[1];
	});

	// search
	jQuery('#searchbttn').button();
	createSuggest('search');

});