<?php
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
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
	require_once('include/config.inc.php');

// if we include footer in some function
	if(!isset($page)) global $page;
	if(!isset($ZBX_PAGE_POST_JS)) global $ZBX_PAGE_POST_JS;
// ---

	if(!defined('PAGE_HEADER_LOADED')){
		define ('PAGE_HEADER_LOADED', 1);
	}

// HISTORY{
	if(isset($page['hist_arg']) && (CWebUser::$data['alias'] != ZBX_GUEST_USER) && ($page['type'] == PAGE_TYPE_HTML) && !defined('ZBX_PAGE_NO_MENU')){
		add_user_history($page);
	}
// HISTORY}

// last page
	if(!defined('ZBX_PAGE_NO_MENU') && ($page['file'] != 'profile.php')){
		CProfile::update('web.paging.lastpage', $page['file'], PROFILE_TYPE_STR);
	}

	CProfile::flush();

// END Transactions if havn't been -----------------
	if(isset($DB) && isset($DB['TRANSACTIONS']) && ($DB['TRANSACTIONS'] != 0)){
		error(S_TRANSACTION_HAVE_NOT_BEEN_CLOSED_ABORTING);
		DBend(false);
	}
//--------------------------------------------------

	show_messages();

	$post_script = '';
	if(uint_in_array($page['type'], array(PAGE_TYPE_HTML_BLOCK, PAGE_TYPE_HTML))){
		if(!is_null(CWebUser::$data) && isset(CWebUser::$data['debug_mode']) && (CWebUser::$data['debug_mode'] == GROUP_DEBUG_MODE_ENABLED)){
			COpt::profiling_stop('script');
			COpt::show();
		}
	}

	if($page['type'] == PAGE_TYPE_HTML){
		$post_script.= 'var page_refresh = null;'."\n";

		if(isset($JS_TRANSLATE)){
			$post_script.='var newLocale = '.zbx_jsvalue($JS_TRANSLATE)."\n";
			$post_script.='var locale = (typeof(locale) == "undefined" ? {} : locale);'."\n";
			$post_script.='for(key in newLocale){locale[key] = newLocale[key];}'."\n";
		}

		$post_script.= 'jQuery(document).ready(function(){'."\n";

		if(isset($ZBX_PAGE_POST_JS)){
			foreach($ZBX_PAGE_POST_JS as $num => $script){
				$post_script.=$script."\n";
			}
		}

		if(defined('ZBX_PAGE_DO_REFRESH') && CWebUser::$data['refresh']){
			$post_script.= 'PageRefresh.init('.(CWebUser::$data['refresh']*1000).');'."\n";
		}

		$post_script.= 'cookie.init();'."\n";
		$post_script.= 'chkbxRange.init();'."\n";

		$post_script.= 'var screenCSS = null;'."\n";
		$post_script.= 'if(jQuery(window).width()<1024) screenCSS = "handheld.css";'."\n";
		$post_script.= 'if(!is_null(screenCSS)) jQuery("head").append(\'<link rel="stylesheet" type="text/css" href="styles/\'+screenCSS+\'" />\');';

		$post_script.='});'."\n";

		if(!defined('ZBX_PAGE_NO_MENU') && !defined('ZBX_PAGE_NO_FOOTER')){
			$table = new CTable(NULL,"textwhite bold maxwidth ui-widget-header ui-corner-all page_footer");
			$table->setCellSpacing(0);
			$table->setCellPadding(1);

			if(CWebUser::$data['userid'] == 0){
				$conString = _('Not connected');
			}
			else if(ZBX_DISTRIBUTED){
				$conString = _s('Connected as \'%1$s\' from \'%2$s\'', CWebUser::$data['alias'], CWebUser::$data['node']['name']);
			}
			else{
				$conString = _s('Connected as \'%1$s\'', CWebUser::$data['alias']);
			}


			$table->addRow(array(
				new CCol(new CLink(
					_s('Zabbix %s Copyright 2001-2011 by Zabbix SIA', ZABBIX_VERSION),
					'http://www.zabbix.com', 'highlight', null, true), 'center'),
				new CCol(array(
					new CSpan(SPACE.SPACE.'|'.SPACE.SPACE, 'divider'),
					new CSpan($conString, 'footer_sign')
				), 'right')
			));
			$table->show();
		}

		insert_js($post_script);

		echo "</body>\n";
		echo "</html>\n";
	}

exit;
?>
