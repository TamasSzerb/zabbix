<?php
// get language translations
require_once('include/gettextwrapper.inc.php');
require_once('include/js.inc.php');
require_once('include/locales.inc.php');

// if we must provide language constants on language different from English
if (isset($_GET['lang'])) {
	if (function_exists('bindtextdomain')) {
		// initializing gettext translations depending on language selected by user
		$locales = zbx_locale_variants($_GET['lang']);
		foreach ($locales as $locale) {
			putenv('LC_ALL='.$locale);
			putenv('LANG='.$locale);
			putenv('LANGUAGE='.$locale);
			if (setlocale(LC_ALL, $locale)) {
				break;
			}
		}
		bindtextdomain('frontend', 'locale');
		bind_textdomain_codeset('frontend', 'UTF-8');
		textdomain('frontend');
	}
	// numeric Locale to default
	setlocale(LC_NUMERIC, array('C', 'POSIX', 'en', 'en_US', 'en_US.UTF-8', 'English_United States.1252', 'en_GB', 'en_GB.UTF-8'));
}

require_once('include/locales/en_gb.inc.php');
$translations = $TRANSLATION;

// available scripts 'scriptFileName' => 'path relative to js/'
$availableJScripts = array(
	'common.js' => '',
	'menu.js' => '',
	'prototype.js' => '',
	'jquery.js' => 'jquery/',
	'jquery-ui.js' => 'jquery/',
	'gtlc.js' => '',
	'functions.js' => '',
	'main.js' => '',
	'dom.js' => '',
	// classes
	'class.bbcode.js' => '',
	'class.calendar.js' => '',
	'class.cdate.js' => '',
	'class.cdebug.js' => '',
	'class.cmap.js' => '',
	'class.cmessages.js' => '',
	'class.cookie.js' => '',
	'class.cscreen.js' => '',
	'class.csuggest.js' => '',
	'class.cswitcher.js' => '',
	'class.ctree.js' => '',
	'class.curl.js' => '',
	'class.rpc.js' => '',
	'class.pmaster.js' => '',
	'class.cviewswitcher.js' => '',
	// templates
	'sysmap.tpl.js' => 'templates/'
);

$tranStrings = array(
	'gtlc.js' => array('S_ALL_S', 'S_ZOOM', 'S_FIXED_SMALL', 'S_DYNAMIC_SMALL', 'S_NOW_SMALL', 'S_YEAR_SHORT',
		'S_MONTH_SHORT', 'S_WEEK_SHORT', 'S_DAY_SHORT', 'S_HOUR_SHORT', 'S_MINUTE_SHORT'
	),
	'functions.js' => array('DO_YOU_REPLACE_CONDITIONAL_EXPRESSION_Q', 'S_INSERT_MACRO', 'S_ADD_SERVICE',
		'S_EDIT_SERVICE', 'S_DELETE_SERVICE', 'S_DELETE_SELECTED_SERVICES_Q', 'S_CREATE_LOG_TRIGGER', 'S_DELETE',
		'S_DELETE_KEYWORD_Q', 'S_DELETE_EXPRESSION_Q', 'S_SIMPLE_GRAPHS', 'S_HISTORY', 'S_HISTORY_AND_SIMPLE_GRAPHS'
	),
	'class.calendar.js' => array('S_JANUARY', 'S_FEBRUARY', 'S_MARCH', 'S_APRIL', 'S_MAY', 'S_JUNE',
		'S_JULY', 'S_AUGUST', 'S_SEPTEMBER', 'S_OCTOBER', 'S_NOVEMBER', 'S_DECEMBER', 'S_MONDAY_SHORT_BIG',
		'S_TUESDAY_SHORT_BIG', 'S_WEDNESDAY_SHORT_BIG', 'S_THURSDAY_SHORT_BIG', 'S_FRIDAY_SHORT_BIG',
		'S_SATURDAY_SHORT_BIG', 'S_SUNDAY_SHORT_BIG', 'S_TIME', 'S_NOW', 'S_DONE'
	),
	'class.cmap.js' => array('S_ON', 'S_OFF', 'S_HIDDEN', 'S_SHOWN', 'S_ERROR', 'S_TYPE', 'S_LABEL', 'S_SHOW', 'S_HIDE',
		'S_HOST', 'S_MAP', 'S_TRIGGER', 'S_SELECT', 'S_HOST_GROUP', 'S_IMAGE', 'S_URL', 'S_URLS', 'S_BOTTOM', 'S_TOP',
		'S_LEFT', 'S_RIGHT', 'S_DEFAULT', 'S_REMOVE', 'S_CLOSE', 'S_PLEASE_SELECT_TWO_ELEMENTS', 'S_ELEMENT', 'S_TRIGGERS',
		'S_COLOR', 'S_ADD', 'S_DESCRIPTION', 'S_NAME', 'S_LINE', 'S_BOLD_LINE', 'S_DOT', 'S_DASHED_LINE', 'S_TWO_ELEMENTS_SHOULD_BE_SELECTED',
		'S_DELETE_SELECTED_ELEMENTS_Q', 'S_PLEASE_SELECT_TWO_ELEMENTS', 'S_NEW_ELEMENT', 'S_SELECT', 'S_INCORRECT_ELEMENT_MAP_LINK',
		'S_EACH_URL_SHOULD_HAVE_UNIQUE', 'S_DELETE_LINKS_BETWEEN_SELECTED_ELEMENTS_Q', 'S_NO_IMAGES', 'S_ICONMAP_IS_NOT_ENABLED'
	),
	'class.cmessages.js' => array('S_MUTE', 'S_UNMUTE', 'S_MESSAGES', 'S_CLEAR', 'S_SNOOZE', 'S_MOVE'),
	'class.cookie.js' => array('S_MAX_COOKIE_SIZE_REACHED'),
	'main.js' => array('S_CLOSE', 'S_NO_ELEMENTS_SELECTED', 'S_INTERFACES')
);

if (empty($_GET['files'])) {
	$files = array(
		'prototype.js',
		'jquery.js',
		'jquery-ui.js',
		'common.js',
		'class.cdebug.js',
		'class.cdate.js',
		'class.cookie.js',
		'class.curl.js',
		'class.rpc.js',
		'class.bbcode.js',
		'class.csuggest.js',
		'class.cmessages.js',
		'main.js',
		'functions.js',
		'menu.js'
	);
}
else {
	$files = $_GET['files'];
}

$js = 'if(typeof(locale) == "undefined") var locale = {};'."\n";
foreach ($files as $file) {
	if (isset($tranStrings[$file])) {
		foreach ($tranStrings[$file] as $str) {
			$js .= "locale['".$str."'] = ".zbx_jsvalue($translations[$str]).";";
		}
	}
}

foreach ($files as $file) {
	if (isset($availableJScripts[$file])) {
		$js .= file_get_contents('js/'.$availableJScripts[$file].$file)."\n";
	}
}

$jsLength = strlen($js);
$ETag = md5($jsLength);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $ETag) {
	header('HTTP/1.1 304 Not Modified');
	header('ETag: '.$ETag);
	exit();
}

header('Content-type: text/javascript; charset=UTF-8');
// breaks if "zlib.output_compression = On"
// header('Content-length: '.$jsLength);
header('Cache-Control: public, must-revalidate');
header('ETag: '.$ETag);

echo $js;
?>
